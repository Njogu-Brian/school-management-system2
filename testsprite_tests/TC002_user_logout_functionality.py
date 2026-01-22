import requests

BASE_URL = "http://localhost:8000"
USERNAME = "b.njogu@royalkingsschools.sc.ke"
PASSWORD = "sRb8s3AAnkYxJ8q"
TIMEOUT = 30

def test_user_logout_functionality():
    # First, login to get token
    login_url = f"{BASE_URL}/login"
    login_payload = {
        "email": USERNAME,
        "password": PASSWORD
    }
    headers = {
        "Content-Type": "application/json"
    }
    try:
        login_response = requests.post(login_url, json=login_payload, headers=headers, timeout=TIMEOUT)
        assert login_response.status_code == 200, f"Login failed with status code {login_response.status_code}"
        # Extract token from response
        login_data = login_response.json()
        token = None
        # Common practice is bearer token or token key in json; assume 'token' key or 'access_token' key
        if "token" in login_data:
            token = login_data["token"]
        elif "access_token" in login_data:
            token = login_data["access_token"]
        else:
            # try 'data' key or similar
            token = login_data.get("data", {}).get("token")
        assert token, "No auth token retrieved after login"

        # Now logout using token in Authorization header
        logout_url = f"{BASE_URL}/logout"
        auth_headers = {
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json"
        }
        logout_response = requests.post(logout_url, headers=auth_headers, timeout=TIMEOUT)
        assert logout_response.status_code == 200, f"Logout failed with status code {logout_response.status_code}"

        # Optionally check something in response body for confirmation
        logout_json = logout_response.json()
        # The spec does not define response body; just ensure success
        assert logout_json is not None, "No response body from logout"

        # Verify token is invalidated by attempting to use it for a secured endpoint
        profile_url = f"{BASE_URL}/user"
        profile_response = requests.get(profile_url, headers=auth_headers, timeout=TIMEOUT)
        # Should fail due to invalid token; check for 401 or 403
        assert profile_response.status_code in (401, 403), "Token still valid after logout"

    except requests.RequestException as e:
        assert False, f"Request failed: {e}"

test_user_logout_functionality()