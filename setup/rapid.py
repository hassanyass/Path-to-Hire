"""
# rapid.py - Backend for Generating AI-Powered Interview Questions

## Overview:
This Flask-based backend serves as an API bridge between the frontend and RapidAPI's "Generate Job Interview Questions" API.
It processes user inputs, sends requests to the external API, and returns generated interview questions.

## How It Works:
1. The frontend (index.html) sends a POST request to `/generate_questions` with parameters such as:
   - `jobRole` (e.g., "Software Engineer")
   - `industry` (e.g., "Technology")
   - `numQuestions` (e.g., 5)
   - `difficulty` (e.g., 7)
   - `topic` (e.g., "Python")

2. The backend forwards this request to the RapidAPI endpoint.
3. The RapidAPI service returns multiple-choice interview questions.
4. The backend extracts only the question text (excluding answer choices) and sends it back to the frontend.

## Frontend Integration:
- The frontend should send a JSON request to `/generate_questions`.
- The response will contain a list of questions formatted as:
  ```json
  {
      "questions": [
          "What are the key principles of object-oriented programming?",
          "Explain the difference between supervised and unsupervised learning.",
          ...
      ]
  }
"""
from flask import Flask, request, jsonify
from flask_cors import CORS
import requests
import json

app = Flask(__name__)
CORS(app)  

RAPIDAPI_KEY = "2209b00400mshf1ed97392df33fdp1f462ajsn38a50811b7c5"
RAPIDAPI_HOST = "generate-job-interview-questions-ai-quick-assess.p.rapidapi.com"

@app.route("/generate_questions", methods=["POST"])
def generate_questions():
    try:
        data = request.json
        print("Received request:", data)  # Debugging log

        url = f"https://{RAPIDAPI_HOST}/generate?noqueue=1"

        payload = {
            "topic": data.get("topic", "General"),
            "numQuestions": int(data.get("numQuestions", 5)),
            "numChoices": 4,
            "difficulty": int(data.get("difficulty", 5)),
            "lang": "en",
            "questionType": "open-ended",
            "skillLevel": "intermediate",
            "jobRole": data.get("jobRole", "Software Engineer"),
            "industry": data.get("industry", "Technology")
        }

        headers = {
            "x-rapidapi-key": RAPIDAPI_KEY,
            "x-rapidapi-host": RAPIDAPI_HOST,
            "Content-Type": "application/json"
        }

        response = requests.post(url, data=json.dumps(payload), headers=headers)
        print("API Response:", response.status_code, response.text)  # Debugging log

        if response.status_code == 200:
            return jsonify(response.json())
        else:
            return jsonify({"error": "Request failed", "status": response.status_code, "details": response.text}), response.status_code

    except Exception as e:
        print("Error:", str(e))  
        return jsonify({"error": str(e)}), 500

if __name__ == "__main__":
    app.run(debug=True, port=5000)
