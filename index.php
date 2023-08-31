<?php

$tasks = [];

// Load tasks from JSON file
function loadTasks()
{
  global $tasks;
  $tasksFile = 'tasks.json';
  $tasksData = file_get_contents($tasksFile);
  $tasks = json_decode($tasksData, true);
}

function displayTasks($tasks)
{
  foreach ($tasks as $index => $task) {
    echo "<li>{$task['title']} (created on: {$task['date_added']}) 
              <form method='post'>
                <input type='hidden' name='deleteIndex' value='$index'>
                <button class='delete-button' type='button' onclick='deleteTask($index)'>Delete</button>
              </form></li>";
  }
}

// Handle form submission for adding tasks using AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addTask'])) {
  $taskTitle = $_POST['taskTitle'];
  $newTask = [
    'title' => $taskTitle,
    'date_added' => date('Y-m-d H:i:s')
  ];

  // Load existing tasks
  loadTasks();

  // Add new task to the tasks array
  $tasks[] = $newTask;

  // Save updated tasks to the JSON file
  $tasksData = json_encode($tasks, JSON_PRETTY_PRINT);
  file_put_contents('tasks.json', $tasksData);

  // Create updated task list HTML
  ob_start();
  displayTasks($tasks);
  $taskListHTML = ob_get_clean();

  // Prepare JSON response
  $response = [
    'success' => true,
    'taskListHTML' => $taskListHTML
  ];

  // Send JSON response
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}

// Handle form submission for deleting tasks using AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteTask'])) {
  $deleteIndex = $_POST['deleteIndex'];

  // Load existing tasks
  loadTasks();

  // Check if the task exists
  if (isset($tasks[$deleteIndex])) {
    unset($tasks[$deleteIndex]);
    // Save updated tasks to the JSON file
    $tasksData = json_encode(array_values($tasks), JSON_PRETTY_PRINT);
    $deleteSuccess = file_put_contents('tasks.json', $tasksData);

    // Create updated task list HTML
    ob_start();
    displayTasks($tasks);
    $taskListHTML = ob_get_clean();

    // Prepare JSON response
    $response = [
      'success' => true,
      'taskListHTML' => $taskListHTML
    ];

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="styles.css" />
  <title>Todo List Manager</title>
  <style>
    .success-message {
      color: green;
      font-weight: bold;
    }

    .error-message {
      color: red;
      font-weight: bold;
    }
  </style>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const addTaskForm = document.getElementById('add-task-form');

      addTaskForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const taskTitleInput = document.getElementById('task-title');
        const taskTitle = taskTitleInput.value;

        // Send task data to the server using fetch
        fetch('index.php', {
          method: 'POST',
          body: new URLSearchParams({
            addTask: '1',
            taskTitle: taskTitle
          })
        })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Update task list dynamically
              const taskList = document.getElementById('task-list');
              taskList.innerHTML = data.taskListHTML;

              // Clear the input field
              taskTitleInput.value = '';
            }
          })
          .catch(error => {
            console.error('Error:', error);
          });
      });
    });
  </script>

  <script>
    function deleteTask(index) {
      // Send task deletion request to the server using fetch
      fetch('index.php', {
        method: 'POST',
        body: new URLSearchParams({
          deleteTask: '1',
          deleteIndex: index
        })
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Update task list dynamically
            const taskList = document.getElementById('task-list');
            taskList.innerHTML = data.taskListHTML;

            // Display success message
            const successMessage = document.createElement('p');
            successMessage.classList.add('success-message');
            successMessage.textContent = 'Task deleted successfully!';
            taskList.appendChild(successMessage);
          } else {
            // Display error message
            const errorMessage = document.createElement('p');
            errorMessage.classList.add('error-message');
            errorMessage.textContent = 'Error deleting task. Please try again.';
            taskList.appendChild(errorMessage);
          }
        })
        .catch(error => {
          console.error('Error:', error);
        });
    }
  </script>

</head>

<body>
  <div class="container">

    <h1>Todo List Manager</h1>

    <!-- Form to add new task -->
    <form id="add-task-form" method="post" action="">
      <input type="text" id="task-title" name="taskTitle" placeholder="Enter task title" required maxlength="100" />
      <button type="submit" name="addTask">Add Task</button>
    </form>

    <!-- List to display tasks -->
    <ul id="task-list">
      <?php
      loadTasks();
      displayTasks($tasks); ?>
    </ul>

  </div>
</body>

</html>