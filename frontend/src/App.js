import React from "react";
import { BrowserRouter as Router, Routes, Route } from "react-router-dom";
import AttendanceRecords from "./components/AttendanceRecords";

function App() {
  return (
    <Router>
      <Routes>
        <Route path="/" element={<AttendanceRecords />} />
      </Routes>
    </Router>
  );
}

export default App;
