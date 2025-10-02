import React, { useState } from 'react';
import { BookOpen, FileText, TrendingUp, Award, Clock, CheckCircle, AlertCircle, BarChart3 } from 'lucide-react';

export default function StudentDashboard() {
  const [studentData] = useState({
    name: "John Doe",
    studentId: "2024-00123",
    courses: [
      {
        id: 1,
        name: "Data Structures and Algorithms",
        code: "CS201",
        progress: 75,
        quizzesTaken: 8,
        totalQuizzes: 12,
        lastActivity: "2 hours ago",
        status: "active"
      },
      {
        id: 2,
        name: "Database Management Systems",
        code: "CS301",
        progress: 60,
        quizzesTaken: 6,
        totalQuizzes: 10,
        lastActivity: "1 day ago",
        status: "active"
      },
      {
        id: 3,
        name: "Web Development",
        code: "CS202",
        progress: 90,
        quizzesTaken: 9,
        totalQuizzes: 10,
        lastActivity: "3 hours ago",
        status: "active"
      }
    ],
    recentQuizzes: [
      {
        id: 1,
        course: "Data Structures",
        score: 85,
        total: 100,
        date: "Today, 10:30 AM",
        topic: "Binary Trees",
        timeSpent: "18 mins",
        status: "completed"
      },
      {
        id: 2,
        course: "Web Development",
        score: 95,
        total: 100,
        date: "Yesterday",
        topic: "React Hooks",
        timeSpent: "20 mins",
        status: "completed"
      },
      {
        id: 3,
        course: "Database Management",
        score: 70,
        total: 100,
        date: "2 days ago",
        topic: "SQL Queries",
        timeSpent: "19 mins",
        status: "needs-improvement"
      }
    ],
    stats: {
      averageScore: 83,
      quizzesCompleted: 23,
      totalTimeSpent: "7.5 hours",
      masteryLevel: "Advanced"
    }
  });

  const getScoreColor = (score) => {
    if (score >= 90) return "text-green-600 bg-green-50";
    if (score >= 75) return "text-blue-600 bg-blue-50";
    if (score >= 60) return "text-yellow-600 bg-yellow-50";
    return "text-red-600 bg-red-50";
  };

  const getProgressColor = (progress) => {
    if (progress >= 80) return "bg-green-500";
    if (progress >= 60) return "bg-blue-500";
    return "bg-yellow-500";
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex justify-between items-center">
            <div>
              <h1 className="text-2xl font-bold text-gray-900">Welcome back, {studentData.name}!</h1>
              <p className="text-sm text-gray-600 mt-1">Student ID: {studentData.studentId}</p>
            </div>
            <div className="flex items-center gap-4">
              <button className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                Take New Quiz
              </button>
            </div>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Stats Overview */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600 font-medium">Average Score</p>
                <p className="text-3xl font-bold text-gray-900 mt-2">{studentData.stats.averageScore}%</p>
              </div>
              <div className="bg-blue-100 p-3 rounded-lg">
                <TrendingUp className="w-6 h-6 text-blue-600" />
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600 font-medium">Quizzes Completed</p>
                <p className="text-3xl font-bold text-gray-900 mt-2">{studentData.stats.quizzesCompleted}</p>
              </div>
              <div className="bg-green-100 p-3 rounded-lg">
                <CheckCircle className="w-6 h-6 text-green-600" />
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600 font-medium">Time Spent Learning</p>
                <p className="text-3xl font-bold text-gray-900 mt-2">{studentData.stats.totalTimeSpent}</p>
              </div>
              <div className="bg-purple-100 p-3 rounded-lg">
                <Clock className="w-6 h-6 text-purple-600" />
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600 font-medium">Mastery Level</p>
                <p className="text-3xl font-bold text-gray-900 mt-2">{studentData.stats.masteryLevel}</p>
              </div>
              <div className="bg-yellow-100 p-3 rounded-lg">
                <Award className="w-6 h-6 text-yellow-600" />
              </div>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* My Courses */}
          <div className="lg:col-span-2">
            <div className="bg-white rounded-lg shadow-sm border border-gray-200">
              <div className="px-6 py-4 border-b border-gray-200">
                <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                  <BookOpen className="w-5 h-5" />
                  My Courses
                </h2>
              </div>
              <div className="p-6 space-y-4">
                {studentData.courses.map(course => (
                  <div key={course.id} className="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors cursor-pointer">
                    <div className="flex justify-between items-start mb-3">
                      <div>
                        <h3 className="font-semibold text-gray-900">{course.name}</h3>
                        <p className="text-sm text-gray-600 mt-1">{course.code}</p>
                      </div>
                      <span className="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                        Active
                      </span>
                    </div>
                    
                    <div className="mb-3">
                      <div className="flex justify-between text-sm mb-2">
                        <span className="text-gray-600">Progress</span>
                        <span className="font-medium text-gray-900">{course.progress}%</span>
                      </div>
                      <div className="w-full bg-gray-200 rounded-full h-2">
                        <div 
                          className={`h-2 rounded-full ${getProgressColor(course.progress)}`}
                          {{-- style={{ width: `${course.progress}%` }} --}}
                        ></div>
                      </div>
                    </div>

                    <div className="flex justify-between items-center text-sm">
                      <span className="text-gray-600">
                        Quizzes: {course.quizzesTaken}/{course.totalQuizzes}
                      </span>
                      <span className="text-gray-500">Last activity: {course.lastActivity}</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Recent Quizzes */}
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 mt-8">
              <div className="px-6 py-4 border-b border-gray-200">
                <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                  <FileText className="w-5 h-5" />
                  Recent Quiz Results
                </h2>
              </div>
              <div className="p-6">
                <div className="space-y-4">
                  {studentData.recentQuizzes.map(quiz => (
                    <div key={quiz.id} className="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                      <div className="flex-1">
                        <div className="flex items-center gap-3">
                          <div className={`w-12 h-12 rounded-lg flex items-center justify-center font-bold text-lg ${getScoreColor(quiz.score)}`}>
                            {quiz.score}
                          </div>
                          <div>
                            <h4 className="font-semibold text-gray-900">{quiz.topic}</h4>
                            <p className="text-sm text-gray-600">{quiz.course}</p>
                            <div className="flex items-center gap-4 mt-1">
                              <span className="text-xs text-gray-500">{quiz.date}</span>
                              <span className="text-xs text-gray-500">⏱ {quiz.timeSpent}</span>
                            </div>
                          </div>
                        </div>
                      </div>
                      <button className="px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-lg transition-colors font-medium">
                        View Details
                      </button>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            {/* Quick Actions */}
            <div className="bg-white rounded-lg shadow-sm border border-gray-200">
              <div className="px-6 py-4 border-b border-gray-200">
                <h2 className="text-lg font-bold text-gray-900">Quick Actions</h2>
              </div>
              <div className="p-6 space-y-3">
                <button className="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center justify-center gap-2">
                  <FileText className="w-4 h-4" />
                  Start New Quiz
                </button>
                <button className="w-full px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium flex items-center justify-center gap-2">
                  <BarChart3 className="w-4 h-4" />
                  View Analytics
                </button>
                <button className="w-full px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium flex items-center justify-center gap-2">
                  <BookOpen className="w-4 h-4" />
                  Browse Courses
                </button>
              </div>
            </div>

            {/* Performance Insights */}
            <div className="bg-white rounded-lg shadow-sm border border-gray-200">
              <div className="px-6 py-4 border-b border-gray-200">
                <h2 className="text-lg font-bold text-gray-900">Performance Insights</h2>
              </div>
              <div className="p-6 space-y-4">
                <div className="flex items-start gap-3">
                  <div className="bg-green-100 p-2 rounded-lg">
                    <CheckCircle className="w-5 h-5 text-green-600" />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-gray-900">Strong Areas</p>
                    <p className="text-xs text-gray-600 mt-1">React Hooks, Binary Trees</p>
                  </div>
                </div>
                
                <div className="flex items-start gap-3">
                  <div className="bg-yellow-100 p-2 rounded-lg">
                    <AlertCircle className="w-5 h-5 text-yellow-600" />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-gray-900">Needs Practice</p>
                    <p className="text-xs text-gray-600 mt-1">SQL Queries, Normalization</p>
                  </div>
                </div>

                <div className="pt-4 border-t border-gray-200">
                  <p className="text-xs text-gray-600">
                    You're performing above average in 2 out of 3 courses. Keep up the great work!
                  </p>
                </div>
              </div>
            </div>

            {/* Study Streak */}
            <div className="bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg shadow-sm p-6 text-white">
              <div className="flex items-center gap-3 mb-4">
                <div className="bg-white bg-opacity-20 p-3 rounded-lg">
                  <Award className="w-6 h-6" />
                </div>
                <div>
                  <p className="text-sm opacity-90">Current Streak</p>
                  <p className="text-2xl font-bold">7 Days</p>
                </div>
              </div>
              <p className="text-sm opacity-90">You're on fire! Complete a quiz today to keep your streak going.</p>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}