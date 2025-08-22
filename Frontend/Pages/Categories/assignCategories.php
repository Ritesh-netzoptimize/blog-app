<?php
session_start();

$categoriesURL = "http://localhost/blog-app/backend/api/v1/category/fetch-all";
$ch = curl_init($categoriesURL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$catResult = curl_exec($ch);
curl_close($ch);

$categories = [];
if ($catResult) {
    $catData = json_decode($catResult, true);
    if ($catData && $catData['success'] === true) {
        $categories = $catData['categories'];
    }
}

// Assume $blogId is passed from previous page
$blogId = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Category</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f8;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .assign-container {
            max-width: 500px;
            margin: 60px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 6px 18px rgba(0,0,0,0.1);
            text-align: center;
        }

        h3 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        select {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 1rem;
            margin-bottom: 15px;
            transition: border-color 0.3s ease;
        }

        select:focus {
            border-color: #3498db;
            outline: none;
        }

        .selected-info {
            margin: 10px 0;
            font-weight: 500;
            color: #16a085;
        }

        .assign-btn {
            padding: 12px 25px;
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .assign-btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        #assignMessage {
            margin-top: 15px;
            padding: 10px;
            font-size: 14px;
            border-radius: 6px;
        }

        #assignMessage p {
            margin: 0;
        }

        #assignMessage p[style*="green"] {
            background: #eafaf1;
            border-left: 4px solid #27ae60;
            color: #2c662d;
            padding: 8px;
        }

        #assignMessage p[style*="red"] {
            background: #fdecea;
            border-left: 4px solid #e74c3c;
            color: #8a1f17;
            padding: 8px;
        }
    </style>
</head>
<body>
    <?php include_once "../../Templates/header.php"?>

    <div class="assign-container">
        <h3>Assign this Blog to a Category</h3>
        <select id="categorySelect">
            <option value="">-- Select Category --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div id="selectedCategory" class="selected-info"></div>

        <button class="assign-btn" id="assignBtn">Assign Category</button>
        <div id="assignMessage"></div>
    </div>

    <script>
        const categorySelect = document.getElementById("categorySelect");
        const selectedCategory = document.getElementById("selectedCategory");
        const assignBtn = document.getElementById("assignBtn");
        const assignMessage = document.getElementById("assignMessage");
        const blogId = <?php echo $blogId; ?>;

        let chosenCategoryId = null;

        categorySelect?.addEventListener("change", function() {
            chosenCategoryId = this.value;
            if (chosenCategoryId) {
                const selectedText = this.options[this.selectedIndex].text;
                selectedCategory.innerHTML = "Selected: " + selectedText;
            } else {
                selectedCategory.innerHTML = "";
            }
        });

        assignBtn?.addEventListener("click", async function() {
            if (!chosenCategoryId) {
                assignMessage.innerHTML = "<p style='color:red;'>Please select a category first.</p>";
                return;
            }
            const payload = {
                blog_id: blogId,
                category_id: chosenCategoryId,
                user_role: "admin"
            };
            try {
                const response = await fetch(`http://localhost/blog-app/backend/api/v1/category/assign-category/${chosenCategoryId}`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (data.success) {
                    assignMessage.innerHTML = "<p style='color:green;'>" + data.message + "</p>";
                } else {
                    assignMessage.innerHTML = "<p style='color:red;'>" + data.message + "</p>";
                }
            } catch (error) {
                assignMessage.innerHTML = "<p style='color:red;'>Something went wrong. Try again.</p>";
            }
        });
    </script>
</body>
</html>
