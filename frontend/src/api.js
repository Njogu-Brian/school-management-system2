import axios from "axios";

const API_URL = "http://127.0.0.1:8000/api"; // Update if needed

export const getAttendanceRecords = async () => {
    try {
        const response = await axios.get(`${API_URL}/attendance`);
        return response.data;
    } catch (error) {
        console.error("Error fetching attendance records:", error);
        return [];
    }
};
