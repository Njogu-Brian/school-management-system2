import requests

BASE_URL = "http://localhost:8000"
TIMEOUT = 30


def test_archive_student_record():
    session = requests.Session()
    headers = {
        "Accept": "application/json",
        "Content-Type": "application/json"
    }

    # Step 0: Get CSRF token by visiting a GET route
    csrf_resp = session.get(f"{BASE_URL}/login", headers=headers, timeout=TIMEOUT)
    assert csrf_resp.status_code == 200, \
        f"Failed to get CSRF token, status code {csrf_resp.status_code}, response: {csrf_resp.text}"

    # Extract CSRF token from cookies
    csrf_token = session.cookies.get('XSRF-TOKEN')
    assert csrf_token is not None, "CSRF token missing in cookies"

    # Add CSRF token to headers for login
    login_headers = headers.copy()
    login_headers["X-CSRF-TOKEN"] = csrf_token

    # Step 1: Login to obtain authentication cookie and CSRF token
    login_payload = {
        "email": "b.njogu@royalkingsschools.sc.ke",
        "password": "sRb8s3AAnkYxJ8q"
    }

    login_resp = session.post(f"{BASE_URL}/login", json=login_payload, headers=login_headers, timeout=TIMEOUT)
    assert login_resp.status_code == 200, \
        f"Login failed, status code {login_resp.status_code}, response: {login_resp.text}"

    student_payload = {
        "first_name": "TestFirstName",
        "last_name": "TestLastName",
        "admission_number": "ARCHIVE-TEST-001",
        "class_id": 1,
        "family_id": None
    }

    student_id = None
    try:
        create_resp = session.post(
            f"{BASE_URL}/students",
            json=student_payload,
            headers=headers,
            timeout=TIMEOUT
        )
        assert create_resp.status_code in (200, 201), \
            f"Failed to create student, status code {create_resp.status_code}, response: {create_resp.text}"

        created_student = create_resp.json()
        student_id = created_student.get("id")
        assert student_id is not None, "No student ID returned on creation"

        # Step 2: Archive the created student
        archive_resp = session.post(
            f"{BASE_URL}/students/{student_id}/archive",
            headers=headers,
            timeout=TIMEOUT
        )
        assert archive_resp.status_code == 200, \
            f"Failed to archive student, status code {archive_resp.status_code}, response: {archive_resp.text}"

        # Step 3: Fetch student details to confirm archive status
        get_resp = session.get(
            f"{BASE_URL}/students/{student_id}",
            headers=headers,
            timeout=TIMEOUT
        )
        assert get_resp.status_code == 200, \
            f"Failed to get student details, status code {get_resp.status_code}, response: {get_resp.text}"

        student_data = get_resp.json()

        archived_flag = student_data.get("archived")
        status = student_data.get("status")

        # Assertions for archived state
        assert (archived_flag is True) or (status and status.lower() == "archived"), \
            f"Student not archived as expected. archived_flag={archived_flag}, status={status}"

        active_flag = student_data.get("active")
        if active_flag is not None:
            assert active_flag is False, "Student active flag should be False after archiving"

    finally:
        if student_id is not None:
            try:
                session.delete(
                    f"{BASE_URL}/students/{student_id}",
                    headers=headers,
                    timeout=TIMEOUT
                )
            except Exception:
                pass


test_archive_student_record()