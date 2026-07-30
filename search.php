<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location:login.php");
    exit();
}
include 'config/db.php';

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$movies = [];

// ---- Build correct poster path whether it's an uploaded file or a pasted URL ----
function posterSrc($poster) {
    if (empty($poster)) return '';
    if (strpos($poster, 'http://') === 0 || strpos($poster, 'https://') === 0) {
        return $poster;
    }
    return 'uploads/' . $poster;
}

if ($search !== '') {
    $searchEsc = $conn->real_escape_string($search);
    $sql = "SELECT * FROM library WHERE title LIKE '%$searchEsc%' OR genre LIKE '%$searchEsc%' ORDER BY id DESC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $movies[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Search | MovieVerse</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Poppins',sans-serif;
}

body{
background:
linear-gradient(rgba(7,7,18,.88), rgba(7,7,18,.92)),
url("https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?q=80&w=1800&auto=format&fit=crop")
center/cover fixed;
min-height:100vh;
color:white;
}

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

.search-hero{
padding:150px 8% 40px;
text-align:center;
}

.search-hero h1{
font-size:36px;
margin-bottom:24px;
}

.search-hero span{
color:#8b5cf6;
}

.big-search{
max-width:600px;
margin:0 auto;
display:flex;
background:rgba(255,255,255,.08);
border:1px solid rgba(255,255,255,.12);
border-radius:40px;
padding:6px;
}

.big-search input{
flex:1;
background:transparent;
border:none;
outline:none;
color:white;
font-size:16px;
padding:12px 20px;
}

.big-search input::placeholder{
color:#999;
}

.big-search button{
background:#8b5cf6;
border:none;
color:white;
padding:0 26px;
border-radius:40px;
cursor:pointer;
font-size:15px;
transition:.3s;
}

.big-search button:hover{
background:#7c3aed;
}

.section{
padding:20px 8% 60px;
}

.section h2{
font-size:22px;
margin-bottom:24px;
color:#bdbdbd;
}

.section h2 span{
color:#8b5cf6;
}

.grid{
display:grid;
grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));
gap:24px;
}

.card{
background:rgba(255,255,255,.06);
border:1px solid rgba(255,255,255,.08);
border-radius:16px;
overflow:hidden;
transition:.35s;
backdrop-filter:blur(10px);
}

.card:hover{
transform:translateY(-8px);
box-shadow:0 10px 30px rgba(139,92,246,.35);
border-color:#8b5cf6;
}

.card-poster{
height:170px;
background:linear-gradient(135deg,#8b5cf6,#3b0764);
display:flex;
align-items:center;
justify-content:center;
font-size:40px;
overflow:hidden;
}

.card-poster img{
width:100%;
height:100%;
object-fit:cover;
}

.card-body{
padding:16px;
}

.card-body h3{
font-size:18px;
margin-bottom:6px;
}

.card-body p{
color:#bdbdbd;
font-size:13px;
margin-bottom:10px;
}

.rating-badge{
display:inline-block;
background:rgba(139,92,246,.2);
color:#c4b5fd;
padding:4px 10px;
border-radius:20px;
font-size:13px;
font-weight:600;
}

.rating-badge i{
color:#fbbf24;
margin-right:4px;
}

.card-actions{
display:flex;
gap:10px;
margin-top:12px;
}

.card-actions a{
flex:1;
text-align:center;
padding:8px;
border-radius:8px;
font-size:13px;
text-decoration:none;
transition:.3s;
}

.edit-link{
background:rgba(139,92,246,.2);
color:#c4b5fd;
}

.edit-link:hover{
background:#8b5cf6;
color:white;
}

.delete-link{
background:rgba(239,68,68,.15);
color:#fca5a5;
}

.delete-link:hover{
background:#ef4444;
color:white;
}

.empty-msg{
color:#999;
text-align:center;
padding:40px;
font-size:15px;
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
.search-hero{
padding:170px 20px 30px;
}
.section{
padding:20px 20px 50px;
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
<a href="search.php">Search</a>
<a href="#">Books</a>
</nav>

<a href="add.php" class="add-btn">
<i class="fa-solid fa-plus"></i>
Add Movie
</a>

<a href="logout.php" style="color:#ddd;text-decoration:none;font-size:14px;display:flex;align-items:center;gap:6px;" title="Logout">
<i class="fa-solid fa-user" style="color:#8b5cf6;"></i>
<?php echo htmlspecialchars($_SESSION['username']); ?>
<i class="fa-solid fa-right-from-bracket"></i>
</a>

</header>

<section class="search-hero">
<h1>Search the <span>MovieVerse</span></h1>

<form class="big-search" method="GET" action="search.php">
<input
type="text"
name="q"
placeholder="Search by title or genre..."
value="<?php echo htmlspecialchars($search); ?>"
autofocus>
<button type="submit">
<i class="fa-solid fa-magnifying-glass"></i>
Search
</button>
</form>

</section>

<section class="section">

<?php if ($search === ''): ?>

<div class="empty-msg">
Type something above to search your movie library.
</div>

<?php elseif (count($movies) === 0): ?>

<div class="empty-msg">
No results found for "<?php echo htmlspecialchars($search); ?>".
</div>

<?php else: ?>

<h2><?php echo count($movies); ?> result<?php echo count($movies) !== 1 ? 's' : ''; ?> for "<span><?php echo htmlspecialchars($search); ?></span>"</h2>

<div class="grid">
<?php foreach ($movies as $movie): ?>

<div class="card">
<div class="card-poster">
<?php if (!empty($movie['poster'])): ?>
<img src="<?php echo htmlspecialchars(posterSrc($movie['poster'])); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
<?php else: ?>
<i class="fa-solid fa-clapperboard"></i>
<?php endif; ?>
</div>
<div class="card-body">
<h3><?php echo htmlspecialchars($movie['title']); ?></h3>
<p>
<?php echo ($movie['type'] === 'Book') ? '📚' : '🎬'; ?>
<?php echo htmlspecialchars($movie['genre']); ?>
</p>
<span class="rating-badge">
<i class="fa-solid fa-star"></i>
<?php echo htmlspecialchars($movie['rating']); ?>
</span>

<div class="card-actions">
<a href="edit.php?id=<?php echo $movie['id']; ?>" class="edit-link">
<i class="fa-solid fa-pen"></i> Edit
</a>
<a href="delete.php?id=<?php echo $movie['id']; ?>"
class="delete-link"
onclick="return confirm('Delete this movie?');">
<i class="fa-solid fa-trash"></i> Delete
</a>
</div>

</div>
</div>

<?php endforeach; ?>
</div>

<?php endif; ?>

</section>

</body>
</html>