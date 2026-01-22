import requests

BASE_URL = "http://localhost:8000"
HEADERS = {"Content-Type": "application/json", "Authorization": "Bearer YOUR_ACCESS_TOKEN"}
TIMEOUT = 30

def test_update_student_information():
    # Create a new student to update
    student_create_payload = {
        "first_name": "TestFirst",
        "last_name": "TestLast",
        "admission_number": "ADM12345UPDATE",
        "class_id": 1,
        "family_id": 1
    }
    
    student_id = None
    try:
        res_create = requests.post(
            f"{BASE_URL}/students",
            json=student_create_payload,
            headers=HEADERS,
            timeout=TIMEOUT
        )
        assert res_create.status_code == 201, f"Unexpected create status: {res_create.status_code}, {res_create.text}"
        student_data = res_create.json()
        student_id = student_data.get("id")
        assert student_id is not None, "Created student response missing ID"
        
        # Update with valid data
        update_payload_valid = {
            "first_name": "UpdatedFirst",
            "last_name": "UpdatedLast",
            "admission_number": "ADM12345UPDATE",
            "class_id": 2
        }
        res_update_valid = requests.put(
            f"{BASE_URL}/students/{student_id}",
            json=update_payload_valid,
            headers=HEADERS,
            timeout=TIMEOUT
        )
        assert res_update_valid.status_code == 200, f"Update with valid data failed: {res_update_valid.status_code}, {res_update_valid.text}"
        updated_student = res_update_valid.json()
        assert updated_student.get("first_name") == "UpdatedFirst"
        assert updated_student.get("last_name") == "UpdatedLast"
        assert updated_student.get("class_id") == 2
        
        # Update with invalid data - e.g., invalid class_id (string instead of integer)
        update_payload_invalid = {
            "first_name": "InvalidUpdate",
            "last_name": "InvalidUpdate",
            "admission_number": "ADM12345UPDATE",
            "class_id": "invalid_class_id"
        }
        res_update_invalid = requests.put(
            f"{BASE_URL}/students/{student_id}",
            json=update_payload_invalid,
            headers=HEADERS,
            timeout=TIMEOUT
        )
        # Expecting client error due to validation failure
        assert res_update_invalid.status_code in {400, 422}, f"Invalid update did not return expected error: {res_update_invalid.status_code}, {res_update_invalid.text}"
        
        # Update non-existing student ID with valid data
        non_existent_id = 99999999
        res_update_nonexistent = requests.put(
            f"{BASE_URL}/students/{non_existent_id}",
            json=update_payload_valid,
            headers=HEADERS,
            timeout=TIMEOUT
        )
        # Expecting 404 not found or similar error
        assert res_update_nonexistent.status_code == 404, f"Updating non-existent student did not return 404: {res_update_nonexistent.status_code}, {res_update_nonexistent.text}"
        
    finally:
        # Cleanup: delete the created student if exists
        if student_id:
            try:
                requests.delete(
                    f"{BASE_URL}/students/{student_id}",
                    headers=HEADERS,
                    timeout=TIMEOUT
                )
            except Exception:
                pass

test_update_student_information()
