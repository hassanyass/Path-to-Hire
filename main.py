from flask import Flask, jsonify, request
from openai import OpenAI
import json
import os

app = Flask(__name__)

# Initialize the OpenAI client
client = OpenAI(api_key="")  # Replace with your OpenAI API key

def generate_feedback_prompt(major, level, field, qa_pairs):
    """Generate a prompt for personalized feedback"""
    prompt = f"""You are an expert interview coach specializing in {major} for {level} candidates in the {field} field. 
Provide detailed feedback on the following interview responses:

1. **Overall Feedback**:
   - Strengths: What was good?
   - Areas of Improvement: What needs improvement?
   - Suggestions: Specific enhancement recommendations.

2. **Question-Specific Feedback**:
   - For each question, provide:
     - The best answer for this question.
     - Feedback on how to improve the candidate's answer.

Here are the questions and answers:
"""
    for idx, pair in enumerate(qa_pairs, 1):
        prompt += f"\nQuestion {idx}: {pair['question']}\nAnswer {idx}: {pair['answer']}\n"
    
    prompt += "\nProvide feedback in the following JSON format:\n"
    prompt += """{
  "overall_feedback": {
    "strengths": "...",
    "weaknesses": "...",
    "suggestions": "..."
  },
  "question_feedback": [
    {
      "question": "...",
      "best_answer": "...",
      "improvement_tips": "..."
    }
  ]
}"""
    return prompt

@app.route('/feedback', methods=['POST'])
def generate_feedback():
    try:
        data = request.get_json()
        print("Received data:", data)

        if not data or 'major' not in data or 'level' not in data or 'field' not in data or 'qa_pairs' not in data:
            return jsonify({'error': 'Invalid request data'}), 400

        major = data['major']
        level = data['level']
        field = data['field']
        qa_pairs = data['qa_pairs']

        # Generate the prompt for the AI model
        prompt = f"""You are an expert interview coach specializing in {major} for {level} candidates in the {field} field. 
Provide detailed feedback on the following interview responses:

1. **Overall Feedback**:
   - Strengths: What was good?
   - Areas of Improvement: What needs improvement?
   - Suggestions: Specific enhancement recommendations.

2. **Question-Specific Feedback**:
   - For each question, provide:
     - The best answer for this question.
     - Feedback on how to improve the candidate's answer.

Here are the questions and answers:
"""
        for idx, pair in enumerate(qa_pairs, 1):
            prompt += f"\nQuestion {idx}: {pair['question']}\nAnswer {idx}: {pair['answer']}\n"
        
        prompt += "\nProvide feedback in the following JSON format:\n"
        prompt += """{
  "overall_feedback": {
    "strengths": "...",
    "weaknesses": "...",
    "suggestions": "..."
  },
  "question_feedback": [
    {
      "question": "...",
      "best_answer": "...",
      "improvement_tips": "..."
    }
  ]
}"""

        # Send the prompt to OpenAI's GPT-3.5-turbo
        try:
            response = client.chat.completions.create(
                model="gpt-3.5-turbo",  # Use GPT-3.5-turbo
                messages=[
                    {"role": "system", "content": "You are an expert interview coach."},
                    {"role": "user", "content": prompt}
                ],
                max_tokens=1500
            )
            feedback = response.choices[0].message.content.strip()

            # Remove any extra characters like ```json
            if feedback.startswith("```json"):
                feedback = feedback[7:]  # Remove ```json
            if feedback.endswith("```"):
                feedback = feedback[:-3]  # Remove ```

            print("Raw feedback from OpenAI:", feedback)

            # Parse the AI response
            feedback_json = json.loads(feedback)
            print("Parsed feedback JSON:", feedback_json)
            return jsonify(feedback_json)
        except Exception as e:
            print("OpenAI API error:", str(e))
            return jsonify({'error': f'OpenAI API error: {str(e)}'}), 500
    except Exception as e:
        print("Internal server error:", str(e))
        return jsonify({'error': f'Internal server error: {str(e)}'}), 500

if __name__ == '__main__':
    app.run(debug=True)