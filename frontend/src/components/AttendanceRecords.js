import React, { useEffect, useState } from "react";
import { getAttendanceRecords } from "../api";

const AttendanceRecords = () => {
    const [records, setRecords] = useState([]);

    useEffect(() => {
        const fetchRecords = async () => {
            const data = await getAttendanceRecords();
            setRecords(data);
        };
        fetchRecords();
    }, []);

    return (
        <div>
            <h1>Attendance Records</h1>
            <table border="1">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Date</th>
                        <th>Present</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    {records.length > 0 ? (
                        records.map((record) => (
                            <tr key={record.id}>
                                <td>{record.student_id}</td>
                                <td>{record.date}</td>
                                <td>{record.is_present ? "Yes" : "No"}</td>
                                <td>{record.reason || "N/A"}</td>
                            </tr>
                        ))
                    ) : (
                        <tr>
                            <td colSpan="4">No records found</td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    );
};

export default AttendanceRecords;
