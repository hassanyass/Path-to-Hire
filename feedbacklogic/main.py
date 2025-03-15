
from flask import Flask, jsonify
import requests
import os

app = Flask(__name__)

# ======================== CONFIGURATION ========================

API_TOKEN = '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'  

#here doma - START EDITING HERE
BACKEND_API_URL = 'http://php-api-url/interview_api.php'  
"""
WHAT: PHP API endpoint URL for fetching Q/A data
CHANGE TO: Your colleague's deployed PHP URL (e.g. http://theirsite.com/interview_api.php)
TESTING: Can use http://localhost:8080/interview_api.php for local development
"""

MODEL_ID = "mistralai/Mistral-7B-Instruct-v0.3"  
"""
WHAT: AI model identifier from Hugging Face
OPTIONAL CHANGE: Can use other models like "meta-llama/Llama-3-70b-chat-hf"
MODEL LIST: https://huggingface.co/models
"""
#here doma - STOP EDITING HERE

headers = {
    'Authorization': f'Bearer {API_TOKEN}',
    'Content-Type': 'application/json'
}


def fetch_interview_data(session_id):
    """Fetch interview questions and answers from backend API"""
    try:
        response = requests.get(
            f"{BACKEND_API_URL}/{session_id}",
            headers={'Content-Type': 'application/json'}
        )
        response.raise_for_status()
        return response.json()
    except requests.exceptions.RequestException as e:
        app.logger.error(f"Backend API error: {str(e)}")
        return None

def format_feedback_prompt(qa_pairs):
    """Structure the prompt for consistent model responses"""
    prompt = """You are an expert interview coach. Provide detailed feedback using this format:

For each response, analyze:
1. **Strengths** - What was good?
2. **Weaknesses** - What needs improvement?
3. **Suggestions** - Specific enhancement recommendations

Evaluate these responses:
"""
    for idx, pair in enumerate(qa_pairs, 1):
        prompt += f"\nQuestion {idx}: {pair['question']}\nAnswer {idx}: {pair['answer']}\n"
    return prompt

def parse_model_response(response_text):
    """Extract and structure the feedback from model response"""
    sections = []
    current_section = []
    
    for line in response_text.split('\n'):
        line = line.strip()
        if line.startswith(('1.', '2.', '3.')):
            if current_section:
                sections.append('\n'.join(current_section))
                current_section = []
            current_section.append(line[3:].strip())  # Remove number prefix
        elif current_section and line:
            current_section.append(line)
    
    if current_section:
        sections.append('\n'.join(current_section))
    
    return {
        "strengths": sections[0] if len(sections) > 0 else "",
        "weaknesses": sections[1] if len(sections) > 1 else "",
        "suggestions": sections[2] if len(sections) > 2 else ""
    }

@app.route('/feedback/<string:session_id>', methods=['GET'])
def generate_feedback(session_id):
    """Endpoint to generate feedback for a specific interview session"""
    # Fetch interview data from backend
    interview_data = fetch_interview_data(session_id)
    
    if not interview_data or 'qa_pairs' not in interview_data:
        return jsonify({'error': 'Interview data not found'}), 404

    # Prepare prompt for AI model
    prompt = format_feedback_prompt(interview_data['qa_pairs'])
    
    # Generate feedback via Hugging Face API
    try:
        response = requests.post(
            f'https://api-inference.huggingface.co/models/{MODEL_ID}',
            headers=headers,
            json={'inputs': prompt, 'parameters': {'max_length': 1000}}
        )
        response.raise_for_status()
    except requests.exceptions.RequestException as e:
        app.logger.error(f"Hugging Face API error: {str(e)}")
        return jsonify({'error': 'Feedback generation failed'}), 500

    # Process and return feedback
    feedback = parse_model_response(response.json()[0]['generated_text'])
    return jsonify({
        'session_id': session_id,
        'feedback': feedback
    })

if __name__ == '__main__':
    app.run(debug=True)
