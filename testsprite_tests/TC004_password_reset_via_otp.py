import requests

BASE_URL = "http://localhost:8000"
TIMEOUT = 30

def test_password_reset_via_otp():
    url = f"{BASE_URL}/password/otp"
    headers = {
        "Content-Type": "application/json"
    }

    # Test case 1: Valid registered phone number (example format)
    valid_phone_payload = {"phone": "+254712345678"}
    try:
        response = requests.post(url, json=valid_phone_payload, headers=headers, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Request failed for valid phone number: {e}"

    # Accept 200 or 404 because the phone might not exist in test environment
    assert response.status_code in (200, 404), f"Expected 200 or 404 for valid phone but got {response.status_code}"
    json_resp = response.json()
    assert isinstance(json_resp, dict), "Response is not a JSON object"
    if response.status_code == 200:
        assert "message" in json_resp or "status" in json_resp, "No success indication in response for valid phone"
    else:
        # 404 case, check for error indication
        message = json_resp.get("message", "").lower()
        errors = json_resp.get("errors", {})
        assert ("unregistered" in message) or ("phone" in errors) or ("not found" in message), \
            "Expected error indication for unregistered phone"

    # Test case 2: Invalid phone number format
    invalid_phone_payload = {"phone": "123-invalid-phone"}
    try:
        response = requests.post(url, json=invalid_phone_payload, headers=headers, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Request failed for invalid phone number: {e}"

    assert response.status_code in (400, 422), f"Expected client error for invalid phone, got {response.status_code}"
    json_resp = response.json()
    assert isinstance(json_resp, dict), "Response is not a JSON object for invalid phone"
    assert "errors" in json_resp or "message" in json_resp, "Expected error message for invalid phone"

    # Test case 3: Unregistered phone number (valid format but not in system)
    unregistered_phone_payload = {"phone": "+254700000000"}
    try:
        response = requests.post(url, json=unregistered_phone_payload, headers=headers, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Request failed for unregistered phone number: {e}"

    # Accept 200 or 404 depending on implementation
    assert response.status_code in (200, 404), f"Unexpected status code for unregistered phone: {response.status_code}"
    json_resp = response.json()
    assert isinstance(json_resp, dict), "Response is not a JSON object for unregistered phone"
    if response.status_code == 200:
        message = json_resp.get("message", "").lower()
        errors = json_resp.get("errors", {})
        assert ("unregistered" in message) or ("phone" in errors) or ("not found" in message), \
            "Expected error indication for unregistered phone"


test_password_reset_via_otp()
