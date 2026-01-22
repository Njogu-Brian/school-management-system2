import requests

def test_get_current_user_profile():
    base_url = "http://localhost:8000"

    login_url = base_url + "/login"
    user_url = base_url + "/user"

    email = "b.njogu@royalkingsschools.sc.ke"
    password = "sRb8s3AAnkYxJ8q"

    headers = {
        "Accept": "application/json",
        "Content-Type": "application/json"
    }

    # Step 1: Login to get auth token
    login_payload = {
        "email": email,
        "password": password
    }

    try:
        login_response = requests.post(login_url, json=login_payload, headers=headers, timeout=30)
    except requests.RequestException as e:
        assert False, f"Login request failed: {e}"

    assert login_response.status_code == 200, f"Expected login status code 200, got {login_response.status_code}"

    try:
        login_data = login_response.json()
    except ValueError:
        assert False, "Login response is not a valid JSON"

    # The PRD doesn't specify exact login response format, but typically token is returned
    # We'll try to get token from 'token' field
    token = login_data.get('token') or login_data.get('access_token') or login_data.get('data', {}).get('token')
    assert token, "Authentication token not found in login response"

    auth_headers = {
        "Accept": "application/json",
        "Authorization": f"Bearer {token}"
    }

    # Step 2: Get current user profile
    try:
        response = requests.get(user_url, headers=auth_headers, timeout=30)
    except requests.RequestException as e:
        assert False, f"Get user profile request failed: {e}"

    assert response.status_code == 200, f"Expected status code 200, got {response.status_code}"

    try:
        user_data = response.json()
    except ValueError:
        assert False, "User profile response is not a valid JSON"

    assert isinstance(user_data, dict), "User profile response should be a JSON object"
    for field in ["id", "email", "name", "roles"]:
        assert field in user_data, f"Missing expected field '{field}' in user profile response"

test_get_current_user_profile()
