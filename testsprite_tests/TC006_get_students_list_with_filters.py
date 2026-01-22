import requests

BASE_URL = "http://localhost:8000"
EMAIL = "b.njogu@royalkingsschools.sc.ke"
PASSWORD = "sRb8s3AAnkYxJ8q"
TIMEOUT = 30

session = requests.Session()


def get_auth_token():
    url = f"{BASE_URL}/login"
    headers = {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest"
    }
    payload = {"email": EMAIL, "password": PASSWORD}
    try:
        response = session.post(url, json=payload, headers=headers, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Login request failed: {e}"
    assert response.status_code == 200, f"Login failed with status {response.status_code}: {response.text}"
    data = response.json()
    assert 'token' in data or 'access_token' in data, "Login response missing auth token"
    token = data.get('token') or data.get('access_token')
    assert isinstance(token, str) and token, "Invalid auth token"
    return token


def test_get_students_list_with_filters():
    token = get_auth_token()
    headers = {
        "Accept": "application/json",
        "Authorization": f"Bearer {token}"
    }

    filter_tests = [
        {},
        {"search": "John"},
        {"class_id": 1},
        {"category_id": 2},
        {"search": "Anna", "class_id": 3},
        {"search": "Mike", "category_id": 4},
        {"class_id": 2, "category_id": 5},
        {"search": "Grace", "class_id": 1, "category_id": 1},
        {"page": 1, "per_page": 5},
        {"search": "a", "page": 2, "per_page": 10}
    ]

    for params in filter_tests:
        try:
            response = session.get(
                f"{BASE_URL}/students",
                headers=headers,
                params=params,
                timeout=TIMEOUT
            )
        except requests.RequestException as e:
            assert False, f"RequestException occurred: {e}"

        assert response.status_code == 200, f"Expected 200 OK but got {response.status_code}. Response: {response.text}"
        try:
            data = response.json()
        except ValueError:
            assert False, "Response is not valid JSON"

        students_list = None
        if isinstance(data, dict):
            if 'data' in data and isinstance(data['data'], list):
                students_list = data['data']
            elif 'students' in data and isinstance(data['students'], list):
                students_list = data['students']
            elif 'items' in data and isinstance(data['items'], list):
                students_list = data['items']
            else:
                students_list = []
        elif isinstance(data, list):
            students_list = data
        else:
            students_list = []

        assert isinstance(students_list, list), f"Expected students list but got {type(students_list)}"

        if students_list:
            for student in students_list:
                assert isinstance(student, dict), f"Student item is not a dict: {student}"
                for key in ["first_name", "last_name", "admission_number", "class_id"]:
                    assert key in student, f"Student missing expected key '{key}': {student}"


test_get_students_list_with_filters()
