# SumakQuiz - Quick Start Guide

## 🚀 For Students - How to Enroll and Take Quizzes

### Prerequisites
Before students can enroll, **teachers must create courses** via the admin panel.

---

## 📖 Step-by-Step Visual Guide

### Step 1: Run Migrations (First Time Setup)
```bash
php artisan migrate
```
This creates the `course_enrollments` table needed for enrollment.

---

### Step 2: Teacher Creates Courses (Admin Panel)

**Teachers must first:**
1. Login to `/admin` (Filament admin panel)
2. Go to "Courses" resource
3. Click "New Course"
4. Fill in:
   - Course Code (e.g., CS101)
   - Course Title (e.g., Data Structures)
   - Description
5. Click "Create"
6. Upload OBTL document (optional)
7. Upload lecture PDFs
8. Wait for processing

**Without teacher-created courses, students will see: "No courses available"**

---

### Step 3: Student Enrolls in Course

**As a Student:**

#### A. Navigate to Courses Page
1. Login to your account
2. Go to `/student/dashboard`
3. Click **"Browse Courses"** button (top right, indigo button)
4. You'll be at `/student/courses`

#### B. View Available Courses Tab

**What you'll see:**

```
┌─────────────────────────────────────────┐
│  Courses                                │
│  Browse and enroll in available courses │
└─────────────────────────────────────────┘

┌────────────────────────────────────────┐
│  My Enrolled Courses (0)  │  Available Courses (5)  │
└────────────────────────────────────────┘
```

**If you have NO enrolled courses:**
- The **"Available Courses"** tab will be shown automatically
- You'll see all courses created by teachers

**If you have enrolled courses:**
- You'll see **"My Enrolled Courses"** tab first
- Click **"Available Courses"** tab to see courses you can enroll in

#### C. Enroll in a Course

**On the Available Courses tab, you'll see cards like:**

```
┌───────────────────────────────────┐
│ CS101                             │
│ Data Structures & Algorithms      │
│ Introduction to programming...    │
│                                   │
│ 👤 Instructor: Dr. Smith          │
│ 📚 12 lectures                    │
│ 👥 45 students enrolled           │
│ ✓ OBTL Available                  │
│                                   │
│  ┌──────────────────────────┐    │
│  │  Enroll in Course        │ <-- CLICK THIS
│  └──────────────────────────┘    │
└───────────────────────────────────┘
```

**Click the blue "Enroll in Course" button**

#### D. Success!
You'll see a green success message:
```
✓ Successfully enrolled in Data Structures & Algorithms!
```

The course will now appear in your **"My Enrolled Courses"** tab.

---

### Step 4: View Course Materials

1. Switch to **"My Enrolled Courses"** tab
2. Click **"View Course"** on your enrolled course
3. You'll see:
   - Course details
   - Lecture materials
   - Subtopic quizzes (with question counts)

---

### Step 5: Take a Quiz

#### A. Click on a Subtopic
From the course detail page, you'll see:

```
Available Quizzes:
┌────────────────────────────┐
│ Arrays Fundamentals        │
│ Introduction to Arrays     │
│              20 questions  │  <-- CLICK THIS
└────────────────────────────┘
```

#### B. Choose Timer Mode
You'll see three options:

```
┌────────────────┐  ┌────────────────┐  ┌────────────────┐
│   🍅 Pomodoro  │  │  ⏰ Free Time  │  │   ⚡ Standard  │
│                │  │                │  │                │
│ 25 min work    │  │ No time limit  │  │ 60s/question   │
│ 5 min breaks   │  │ Your own pace  │  │ Auto-submit    │
│                │  │                │  │                │
│   [SELECT]     │  │   [SELECT]     │  │   [SELECT]     │
└────────────────┘  └────────────────┘  └────────────────┘
```

**Click one** to proceed.

#### C. Review Quiz Info
You'll see a summary:
- 20 multiple-choice questions
- Your selected timer mode
- Immediate feedback after each answer

**Click "Start Quiz"**

#### D. Answer Questions
For each question:
1. Read the question
2. See timer (if not Free Time mode)
3. Select answer (A, B, C, or D)
4. Click "Submit Answer"
5. See immediate feedback (✓ or ✗)
6. Read explanation
7. Click "Next Question"

#### E. Complete Quiz
After answering all 20 questions, you'll see your score!

---

### Step 6: View AI Feedback

The results page shows:
- **Score:** 85% (17/20 correct)
- **Time Spent:** 18 minutes
- **Status:** Passed ✓

**AI Analysis includes:**
- ✅ **Strengths:** What you did well
- 📈 **Areas to Improve:** Topics to study more
- 💡 **Recommendations:** Personalized study tips

---

### Step 7: Retake Quiz (Optional)

**From the results page:**
- Click **"Retake Quiz"** button
- Choose timer mode again
- Take the quiz with potentially reworded questions
- Can retake up to 3 times total

---

## 🔍 Troubleshooting

### "No courses available"
**Cause:** No teacher has created courses yet  
**Solution:** Ask your instructor to create courses via `/admin`

### "Can't see enroll button"
**Cause:** You're on the wrong tab  
**Solution:** Click the **"Available Courses"** tab (it should auto-select if you have no enrollments)

### "Already enrolled" message
**Cause:** You clicked enroll on a course you're already in  
**Solution:** Check the **"My Enrolled Courses"** tab

### "Must enroll first" error when viewing course
**Cause:** Trying to access a course you're not enrolled in  
**Solution:** Go to `/student/courses` and enroll first

---

## 📍 Quick Links for Students

| Action | Click This | URL |
|--------|-----------|-----|
| View Dashboard | Dashboard link in nav | `/student/dashboard` |
| Browse Courses | "Browse Courses" button | `/student/courses` |
| Enroll | "Enroll in Course" button | On Available Courses tab |
| View Course | "View Course" button | After enrolling |
| Take Quiz | Click subtopic name | From course detail page |
| View Results | Automatic | After completing quiz |

---

## 🎯 Visual Flow Diagram

```
Student Login
    ↓
/student/dashboard
    ↓ [Click "Browse Courses"]
/student/courses
    ↓ [Auto-shows "Available Courses" tab]
See Course Cards
    ↓ [Click "Enroll in Course" button on a card]
✓ Enrolled Successfully!
    ↓ [Go to "My Enrolled Courses" tab]
See Your Course
    ↓ [Click "View Course"]
/student/course/{id}
    ↓ [Click on a subtopic]
/student/quiz/{subtopic}
    ↓ [Choose: Pomodoro / Free Time / Standard]
Selected Timer Mode
    ↓ [Click "Start Quiz"]
Take Quiz (20 questions)
    ↓ [Answer all questions]
/student/quiz/{attempt}/result
    ↓
AI Feedback & Results!
    ↓ [Optional: Click "Retake Quiz"]
```

---

## ✅ What You Should See

### On `/student/courses`:

**Tab 1: "My Enrolled Courses (0)"** ← Automatically skipped if empty  
**Tab 2: "Available Courses (5)"** ← You start here!

Each available course shows:
- Course code and title
- Instructor name
- Number of lectures
- Number of enrolled students
- **Blue "Enroll in Course" button** ← THIS IS THE BUTTON TO CLICK!

---

## 💡 Pro Tips

1. **First time?** The page will automatically show "Available Courses" tab
2. **Want to see enrolled courses?** Click the "My Enrolled Courses" tab
3. **Looking for a course?** Check the "Available Courses" tab
4. **Can't find a course?** Ask your instructor to create it in `/admin`
5. **Choose Pomodoro** for focused study sessions
6. **Choose Free Time** when learning new concepts
7. **Choose Standard** for quick testing

---

**The "Enroll in Course" button is visible on the "Available Courses" tab!**