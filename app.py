from flask import Flask, request, jsonify
from flask_cors import CORS
from openai import OpenAI
import json

app = Flask(__name__)
CORS(app)

# Initialize OpenAI client
client = OpenAI(api_key="")  #Please refer to the readme file


# Endpoint to generate questions
@app.route("/generate_questions", methods=["POST"])
def generate_questions():
    try:
        # Get data from the request
        data = request.get_json()
        print("Received request:", data)

        # Extract parameters
        job_role = data.get("jobRole", "Software Engineer")
        industry = data.get("industry", "Technology")
        num_questions = int(data.get("numQuestions", 10))
        level_id = int(data.get("level_id", 1))  # Get level_id from the request
        topic = data.get("topic", "General")

        # Set difficulty based on level_id
        if level_id == 1:
            difficulty = 4
        elif level_id == 2:
            difficulty = 7
        elif level_id == 3:
            difficulty = 10
        else:
            difficulty = 5  # Default difficulty if level_id is invalid

        # Generate prompt for OpenAI
        prompt = f"""You are an expert interview coach specializing in {job_role} for the {industry} industry.
Generate {num_questions} interview questions for a candidate with the following details:
- Job Role: {job_role}
- Industry: {industry}
- Difficulty Level: {difficulty}/10
- Topic: {topic}

Provide the questions as a numbered list.
"""

        # Call OpenAI API
        response = client.chat.completions.create(
            model="gpt-3.5-turbo",  # Use GPT-3.5-turbo
            messages=[
                {"role": "system", "content": "You are an expert interview question generator."},
                {"role": "user", "content": prompt}
            ],
            max_tokens=1000
        )

        # Extract questions from the response
        questions = response.choices[0].message.content.strip().split("\n")
        questions = [q.split(". ", 1)[1] for q in questions if q.strip()]  # Remove numbering and clean up

        # Ensure exactly 10 questions are returned
        questions = questions[:num_questions]

        # Return the questions as JSON
        return jsonify({"questions": questions})
    except Exception as e:
        print("Error:", str(e))
        return jsonify({"error": str(e)}), 500

# Endpoint to generate feedback
@app.route("/feedback", methods=["POST"])
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
    # Run the Flask app with threading enabled
    app.run(debug=True, threaded=True, port=5000)
