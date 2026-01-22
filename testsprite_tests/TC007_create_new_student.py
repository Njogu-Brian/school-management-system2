import requests
from requests.auth import HTTPBasicAuth

BASE_URL = "http://localhost:8000"
USERNAME = "b.njogu@royalkingsschools.sc.ke"
PASSWORD = "sRb8s3AAnkYxJ8q"
TIMEOUT = 30

def test_create_new_student():
    auth = HTTPBasicAuth(USERNAME, PASSWORD)
    headers = {"Content-Type": "application/json"}

    # Helper to create a family to get a valid family_id
    def create_family():
        family_data = {}
        resp = requests.post(f"{BASE_URL}/families", json=family_data, auth=auth, headers=headers, timeout=TIMEOUT)
        assert resp.status_code in (200, 201), f"Failed to create family, status code {resp.status_code}"
        resp_json = resp.json()
        family_id = resp_json.get("id")
        assert family_id is not None, "Response JSON missing 'id' for created family"
        return family_id

    family_id = create_family()
    assert family_id is not None, "Failed to create family for student creation"

    student_payload_valid = {
        "first_name": "John",
        "last_name": "Doe",
        "admission_number": "ADM123456",
        "class_id": 1,
        "family_id": family_id
    }

    # Test valid student creation
    response = requests.post(f"{BASE_URL}/students", json=student_payload_valid, auth=auth, timeout=TIMEOUT, headers=headers)
    assert response.status_code in (200, 201), f"Expected 200 or 201, got {response.status_code}"
    resp_json = response.json()
    assert "id" in resp_json, "Response JSON missing 'id' for created student"
    student_id = resp_json["id"]
    assert resp_json["first_name"] == student_payload_valid["first_name"]
    assert resp_json["last_name"] == student_payload_valid["last_name"]
    assert resp_json["admission_number"] == student_payload_valid["admission_number"]
    assert resp_json["family_id"] == family_id

    # Test invalid data: missing required field first_name
    invalid_payload_missing_first_name = {
        "last_name": "Doe",
        "admission_number": "ADM123457",
        "class_id": 1,
        "family_id": family_id
    }
    response_invalid_1 = requests.post(f"{BASE_URL}/students", json=invalid_payload_missing_first_name, auth=auth, timeout=TIMEOUT, headers=headers)
    assert response_invalid_1.status_code in (400, 422), f"Expected 400 or 422 for missing first_name, got {response_invalid_1.status_code}"

    # Test invalid data: invalid family_id (non-existent)
    invalid_payload_bad_family = {
        "first_name": "Jane",
        "last_name": "Doe",
        "admission_number": "ADM123458",
        "class_id": 1,
        "family_id": 9999999
    }
    response_invalid_2 = requests.post(f"{BASE_URL}/students", json=invalid_payload_bad_family, auth=auth, timeout=TIMEOUT, headers=headers)
    assert response_invalid_2.status_code in (400, 422), f"Expected 400 or 422 for invalid family_id, got {response_invalid_2.status_code}"

    # Test invalid data: missing mandatory admission_number
    invalid_payload_missing_adm = {
        "first_name": "Jane",
        "last_name": "Doe",
        "class_id": 1,
        "family_id": family_id
    }
    response_invalid_3 = requests.post(f"{BASE_URL}/students", json=invalid_payload_missing_adm, auth=auth, timeout=TIMEOUT, headers=headers)
    assert response_invalid_3.status_code in (400, 422), f"Expected 400 or 422 for missing admission_number, got {response_invalid_3.status_code}"


test_create_new_student()