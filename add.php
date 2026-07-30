<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location:login.php");
    exit();
}
include 'config/db.php';

if(isset($_POST['save']))
{
    $title = $_POST['title'];
    $genre = $_POST['genre'];
    $rating = $_POST['rating'];
    $type = isset($_POST['type']) && $_POST['type'] === 'Book' ? 'Book' : 'Movie';
    $validIndustries = ['Bollywood', 'Hollywood', 'South Indian', 'Other'];
    $industry = (isset($_POST['industry']) && in_array($_POST['industry'], $validIndustries)) ? $_POST['industry'] : 'Other';

    $titleEsc  = $conn->real_escape_string($title);
    $genreEsc  = $conn->real_escape_string($genre);
    $ratingEsc = $conn->real_escape_string($rating);
    $typeEsc   = $conn->real_escape_string($type);
    $industryEsc = $conn->real_escape_string($industry);

    // ---- Handle poster: file upload OR pasted URL (file upload takes priority) ----
    $posterName = null;

    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $fileType = $_FILES['poster']['type'];

        if (in_array($fileType, $allowedTypes)) {

            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION);
            $posterName = uniqid('movie_') . '.' . $ext;
            move_uploaded_file($_FILES['poster']['tmp_name'], $uploadDir . $posterName);
        }
    } elseif (!empty($_POST['poster_url'])) {
        // No file uploaded, but a URL was pasted instead
        $posterName = trim($_POST['poster_url']);
    }

    $posterEsc = $posterName ? $conn->real_escape_string($posterName) : null;

    if ($posterEsc) {
        $sql = "INSERT INTO library(title,genre,rating,poster,type,industry)
                VALUES('$titleEsc','$genreEsc','$ratingEsc','$posterEsc','$typeEsc','$industryEsc')";
    } else {
        $sql = "INSERT INTO library(title,genre,rating,type,industry)
                VALUES('$titleEsc','$genreEsc','$ratingEsc','$typeEsc','$industryEsc')";
    }

    if($conn->query($sql))
    {
        header("Location:index.php");
        exit();
    }
    else
    {
        echo "Error : ".$conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Add Movie | MovieVerse</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Poppins',sans-serif;
}

body{

background:
linear-gradient(rgba(7,7,18,.88),
rgba(7,7,18,.92)),
url("https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?q=80&w=1800&auto=format&fit=crop")
center/cover fixed;

min-height:100vh;

color:white;

}

/* NAVBAR */

header{

position:fixed;

top:0;
left:0;

width:100%;

padding:18px 8%;

display:flex;

justify-content:space-between;

align-items:center;

background:rgba(0,0,0,.45);

backdrop-filter:blur(12px);

z-index:1000;

}

.logo{

font-size:30px;

font-weight:700;

text-decoration:none;

color:#8b5cf6;

}

.logo span{

color:white;

}

nav a{

color:#ddd;

text-decoration:none;

margin:0 18px;

transition:.3s;

font-size:15px;

}

nav a:hover{

color:#8b5cf6;

}

.add-btn{

background:#8b5cf6;

padding:10px 20px;

border-radius:30px;

text-decoration:none;

color:white;

font-weight:600;

transition:.3s;

}

.add-btn:hover{

transform:translateY(-3px);

box-shadow:0 0 18px rgba(139,92,246,.5);

}

/* FORM */

.wrapper{

display:flex;

justify-content:center;

align-items:center;

padding-top:130px;

padding-bottom:60px;

}

.container{

width:470px;

background:rgba(255,255,255,.06);

border:1px solid rgba(255,255,255,.08);

backdrop-filter:blur(18px);

border-radius:20px;

padding:35px;

animation:fadeUp .8s ease;

box-shadow:0 0 30px rgba(0,0,0,.35);

}

.container h2{

text-align:center;

margin-bottom:8px;

font-size:30px;

}

.container p{

text-align:center;

color:#bdbdbd;

margin-bottom:30px;

font-size:14px;

}

label{

display:block;

margin-bottom:8px;

margin-top:18px;

font-size:15px;

}

.input-box{

position:relative;

}

.input-box i{

position:absolute;

left:15px;

top:15px;

color:#8b5cf6;

}

input,
select{

width:100%;

padding:14px 14px 14px 45px;

background:#171727;

border:1px solid transparent;

border-radius:10px;

color:white;

font-size:15px;

transition:.3s;

}

input:focus,
select:focus{

outline:none;

border-color:#8b5cf6;

box-shadow:0 0 15px rgba(139,92,246,.35);

}

button{

width:100%;

margin-top:30px;

padding:15px;

border:none;

border-radius:12px;

background:#8b5cf6;

color:white;

font-size:16px;

font-weight:600;

cursor:pointer;

transition:.3s;

}

button:hover{

transform:translateY(-4px);

box-shadow:0 0 20px rgba(139,92,246,.45);

background:#7c3aed;

}

.back{

display:block;

text-align:center;

margin-top:22px;

color:#bdbdbd;

text-decoration:none;

transition:.3s;

}

.back:hover{

color:#8b5cf6;

}

@keyframes fadeUp{

from{

opacity:0;

transform:translateY(40px);

}

to{

opacity:1;

transform:translateY(0);

}

}

@media(max-width:768px){

header{

padding:15px 20px;

flex-direction:column;

gap:15px;

}

nav{

display:flex;

gap:15px;

}

.wrapper{

padding:170px 20px 40px;

}

.container{

width:100%;

}

}

</style>

</head>

<body>

<header>

<a href="index.php" class="logo">
Movie<span>Verse</span>
</a>

<nav>

<a href="index.php">Home</a>
<a href="#">Movies</a>
<a href="#">Books</a>

</nav>

<a href="index.php" class="add-btn">

<i class="fa-solid fa-house"></i>

Home

</a>

</header>

<div class="wrapper">

<div class="container">

<h2>🎬 Add New Item</h2>

<p>
Fill in the details below to add a new movie or book to your collection.
</p>

<form method="POST" enctype="multipart/form-data">

<label>Type</label>

<div class="input-box">

<i class="fa-solid fa-shapes"></i>

<select name="type" required style="padding-left:45px;">
<option value="Movie">🎬 Movie</option>
<option value="Book">📚 Book</option>
</select>

</div>

<label>Industry</label>

<div class="input-box">

<i class="fa-solid fa-globe"></i>

<select name="industry" required style="padding-left:45px;">
<option value="Bollywood">Bollywood</option>
<option value="Hollywood">Hollywood</option>
<option value="South Indian">South Indian</option>
<option value="Other">Other</option>
</select>

</div>

<label>Movie Poster (upload a file)</label>

<div class="input-box">

<i class="fa-solid fa-image"></i>

<input
type="file"
name="poster"
accept="image/*"
style="padding-left:45px;">

</div>

<label>OR paste a poster image URL</label>

<div class="input-box">

<i class="fa-solid fa-link"></i>

<input
type="text"
name="poster_url"
placeholder="https://placehold.co/500x750/8b5cf6/white?text=Movie+Name">

</div>

<label>Title</label>

<div class="input-box">

<i class="fa-solid fa-film"></i>

<input
type="text"
name="title"
placeholder="Enter movie title"
required>

</div>

<label>Genre</label>

<div class="input-box">

<i class="fa-solid fa-layer-group"></i>

<select name="genre" required>

<option value="">Select Genre</option>

<option>Action</option>
<option>Adventure</option>
<option>Comedy</option>
<option>Drama</option>
<option>Fantasy</option>
<option>Horror</option>
<option>Romance</option>
<option>Sci-Fi</option>
<option>Thriller</option>

</select>

</div>

<label>Rating</label>

<div class="input-box">

<i class="fa-solid fa-star"></i>

<input
type="number"
name="rating"
step="0.1"
min="0"
max="10"
placeholder="Example : 8.5"
required>

</div>

<button type="submit" name="save">

<i class="fa-solid fa-plus"></i>

Save Movie

</button>

</form>

<a href="index.php" class="back">

<i class="fa-solid fa-arrow-left"></i>

Back to Home

</a>

</div>

</div>

</body>
</html>