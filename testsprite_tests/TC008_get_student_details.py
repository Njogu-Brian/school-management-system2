import requests

BASE_URL = "http://localhost:8000"
TIMEOUT = 30

def test_get_student_details():
    headers = {
        "Accept": "application/json"
    }

    # Step 1: Create a new student to ensure we have a valid ID
    student_data = {
        "first_name": "TestFirstName",
        "last_name": "TestLastName",
        "admission_number": "TEST123456",
        "class_id": 1  # Assuming class_id 1 exists; if not, this may need adjustment
    }
    student_id = None
    try:
        create_resp = requests.post(f"{BASE_URL}/students", json=student_data, headers=headers, timeout=TIMEOUT)
        assert create_resp.status_code in (200, 201), f"Expected 201/200 on student creation, got {create_resp.status_code}"
        create_resp_json = create_resp.json()
        assert "id" in create_resp_json, "Response JSON missing 'id' of created student"
        student_id = create_resp_json["id"]

        # Step 2: Retrieve the newly created student's details (success case)
        get_resp = requests.get(f"{BASE_URL}/students/{student_id}", headers=headers, timeout=TIMEOUT)
        assert get_resp.status_code == 200, f"Expected 200 on getting student details, got {get_resp.status_code}"
        student_detail = get_resp.json()
        # Basic validation on returned student data
        assert isinstance(student_detail, dict), "Student detail response is not a JSON object"
        assert student_detail.get("id") == student_id, "Returned student ID does not match requested ID"
        assert student_detail.get("first_name") == student_data["first_name"], "Student first_name does not match"
        assert student_detail.get("last_name") == student_data["last_name"], "Student last_name does not match"
        assert student_detail.get("admission_number") == student_data["admission_number"], "Student admission_number does not match"

        # Step 3: Test retrieval of non-existent student ID
        non_existent_id = 9999999999  # Large number unlikely to exist
        if student_id == non_existent_id:
            non_existent_id += 1  # Just in case
        non_exist_resp = requests.get(f"{BASE_URL}/students/{non_existent_id}", headers=headers, timeout=TIMEOUT)
        assert non_exist_resp.status_code == 404, f"Expected 404 for non-existent student ID, got {non_exist_resp.status_code}"

    finally:
        # Cleanup: skip deletion as DELETE endpoint not specified in PRD
        pass

test_get_student_details()
