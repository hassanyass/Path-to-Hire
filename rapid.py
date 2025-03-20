from flask import Flask, request, jsonify
from flask_cors import CORS
from openai import OpenAI
import os

app = Flask(__name__)
CORS(app)


client = OpenAI(api_key="")

@app.route("/generate_questions", methods=["POST"])
def generate_questions():
    try:
        
        data = request.get_json()
        print("Received request:", data)

        
        job_role = data.get("jobRole", "Software Engineer")
        industry = data.get("industry", "Technology")
        num_questions = int(data.get("numQuestions", 10))
        level_id = int(data.get("level_id", 1))  
        topic = data.get("topic", "General")

        
        if level_id == 1:
            difficulty = 4
        elif level_id == 2:
            difficulty = 7
        elif level_id == 3:
            difficulty = 10
        else:
            difficulty = 5  

        
        prompt = f"""You are an expert interview coach specializing in {job_role} for the {industry} industry.
Generate {num_questions} interview questions for a candidate with the following details:
- Job Role: {job_role}
- Industry: {industry}
- Difficulty Level: {difficulty}/10
- Topic: {topic}

Provide the questions as a numbered list.
"""

        
        response = client.chat.completions.create(
            model="gpt-3.5-turbo", 
            messages=[
                {"role": "system", "content": "You are an expert interview question generator."},
                {"role": "user", "content": prompt}
            ],
            max_tokens=1000
        )

        
        questions = response.choices[0].message.content.strip().split("\n")
        questions = [q.split(". ", 1)[1] for q in questions if q.strip()]  # Remove numbering and clean up

        
        questions = questions[:num_questions]

        
        return jsonify({"questions": questions})
    except Exception as e:
        print("Error:", str(e))
        return jsonify({"error": str(e)}), 500

if __name__ == "__main__":
    app.run(debug=True, port=5000)
