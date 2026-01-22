import requests

BASE_URL = "http://localhost:8000"
AUTH_USERNAME = "b.njogu@royalkingsschools.sc.ke"
AUTH_PASSWORD = "sRb8s3AAnkYxJ8q"
TIMEOUT = 30

def test_user_login_functionality():
    headers = {
        "Content-Type": "application/json",
        "Accept": "application/json"
    }

    # Valid credentials test with remember=True
    valid_payload = {
        "email": AUTH_USERNAME,
        "password": AUTH_PASSWORD,
        "remember": True
    }
    try:
        response = requests.post(
            f"{BASE_URL}/login",
            json=valid_payload,
            headers=headers,
            timeout=TIMEOUT
        )
        response.raise_for_status()
        assert response.status_code == 200, f"Expected status code 200 but got {response.status_code}"
    except requests.RequestException as e:
        assert False, f"Request failed for valid credentials: {e}"

    # Valid credentials test with remember=False
    valid_payload["remember"] = False
    try:
        response = requests.post(
            f"{BASE_URL}/login",
            json=valid_payload,
            headers=headers,
            timeout=TIMEOUT
        )
        response.raise_for_status()
        assert response.status_code == 200, f"Expected status code 200 but got {response.status_code}"
    except requests.RequestException as e:
        assert False, f"Request failed for valid credentials with remember=False: {e}"

    # Invalid credentials test (wrong password)
    invalid_payload = {
        "email": AUTH_USERNAME,
        "password": "WrongPassword123!",
        "remember": True
    }
    try:
        response = requests.post(
            f"{BASE_URL}/login",
            json=invalid_payload,
            headers=headers,
            timeout=TIMEOUT
        )
        # Expecting failure, typically 401 or 400
        assert response.status_code in (400, 401), f"Expected 400 or 401 for invalid credentials but got {response.status_code}"
    except requests.RequestException:
        # A network exception could be OK here since we expect failure, but log as assert fail.
        assert False, "Request failed unexpectedly on invalid credentials test"

    # Invalid email format test
    invalid_email_payload = {
        "email": "invalid-email-format",
        "password": AUTH_PASSWORD,
        "remember": True
    }
    try:
        response = requests.post(
            f"{BASE_URL}/login",
            json=invalid_email_payload,
            headers=headers,
            timeout=TIMEOUT
        )
        # API might reject with 400 for invalid email format
        assert response.status_code in (400, 422), f"Expected 400 or 422 for invalid email format but got {response.status_code}"
    except requests.RequestException:
        assert False, "Request failed unexpectedly on invalid email format test"

    # Missing password test
    missing_password_payload = {
        "email": AUTH_USERNAME,
        "remember": True
    }
    try:
        response = requests.post(
            f"{BASE_URL}/login",
            json=missing_password_payload,
            headers=headers,
            timeout=TIMEOUT
        )
        assert response.status_code in (400, 422), f"Expected 400 or 422 for missing password but got {response.status_code}"
    except requests.RequestException:
        assert False, "Request failed unexpectedly on missing password test"

    # Missing email test
    missing_email_payload = {
        "password": AUTH_PASSWORD,
        "remember": True
    }
    try:
        response = requests.post(
            f"{BASE_URL}/login",
            json=missing_email_payload,
            headers=headers,
            timeout=TIMEOUT
        )
        assert response.status_code in (400, 422), f"Expected 400 or 422 for missing email but got {response.status_code}"
    except requests.RequestException:
        assert False, "Request failed unexpectedly on missing email test"

test_user_login_functionality()
