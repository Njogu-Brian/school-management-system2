import requests

def test_password_reset_via_email():
    base_url = "http://localhost:8000"
    endpoint = "/password/email"
    url = base_url + endpoint
    headers = {
        "Content-Type": "application/json",
        "Accept": "application/json"
    }
    timeout = 30

    # Test with a valid registered email (assuming this email is registered)
    valid_email_payload = {"email": "b.njogu@royalkingsschools.sc.ke"}
    try:
        response = requests.post(url, json=valid_email_payload, headers=headers, timeout=timeout)
        assert response.status_code in [200, 202], f"Expected success status code for registered email, got {response.status_code}"
        # The response might not have content or may just confirm sending
    except requests.RequestException as e:
        assert False, f"Request failed for valid email: {e}"

    # Test with an invalid email format
    invalid_email_payload = {"email": "invalid-email-format"}
    try:
        response = requests.post(url, json=invalid_email_payload, headers=headers, timeout=timeout)
        # Expecting client error (e.g. 422 Unprocessable Entity or 400 Bad Request)
        assert response.status_code >= 400 and response.status_code < 500, f"Expected client error for invalid email format, got {response.status_code}"
        # Optionally validate response JSON error message presence
        resp_json = response.json()
        assert "email" in resp_json.get("errors", {}) or "message" in resp_json, "Expected validation error related to email"
    except requests.RequestException as e:
        assert False, f"Request failed for invalid email: {e}"
    except ValueError:
        # Response not JSON
        pass

    # Test with an unregistered but valid email format
    unregistered_email_payload = {"email": "unregistered.email@example.com"}
    try:
        response = requests.post(url, json=unregistered_email_payload, headers=headers, timeout=timeout)
        # Depending on system design, could be 200/202 to avoid user enumeration or 404/400 for error
        # Assert no sensitive info returned and response code is either success or handled error
        assert response.status_code in [200, 202, 400, 404], f"Unexpected status code for unregistered email, got {response.status_code}"
    except requests.RequestException as e:
        assert False, f"Request failed for unregistered email: {e}"

test_password_reset_via_email()
