# Path to Hire - Setup Guide

## Prerequisites
Before setting up the project, ensure you have the following installed on your system:
- [XAMPP](https://www.apachefriends.org/index.html)
- [Python](https://www.python.org/downloads/)
- Virtual Environment (`venv`)

## Installation Steps
Follow these steps to set up and run the project:

### Step 1: Clone the Repository
1. Clone the project from the repository:
   ```bash
   git clone <https://github.com/hassanyass/Path-to-Hire.git>
   ```
2. Move the cloned project folder to `htdocs` inside the XAMPP directory.

### Step 2: Setup the Database
1. Open **XAMPP** and start **Apache** and **MySQL** services.
2. Open your browser and go to **phpMyAdmin**:
   ```
   http://localhost/phpmyadmin
   ```
3. Create a new database named **PathToHire**.

4. Import the `PathToHire.sql` file:
   - Click on **Import**.
   - Choose `PathToHire.sql` from the project folder.
   - Click **Go** to import.

### Step 3: Setup the Virtual Environment and Run the Application
1. Open a terminal or command prompt.
2. Navigate to the project directory.
3. Activate the virtual environment:
   - **Windows**:
     ```bash
     venv\Scripts\activate
     ```
   - **Mac/Linux**:
     ```bash
     source venv/bin/activate
     ```
4. Run the application:
   ```bash
   python app.py
   ```

### Step 4: Access the Project in the Browser
1. Open your browser.
2. Enter the following URL:
   ```
   http://localhost/Path-to-Hire/index.php
   ```
3. Enjoy the experience!

---
For any issues, feel free to contact the project maintainer.