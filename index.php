<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location:login.php");
    exit();
}
include 'config/db.php';

// ---- Search & Filter ----
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$genreFilter = isset($_GET['genre']) ? trim($_GET['genre']) : '';
$typeFilter = isset($_GET['type']) ? trim($_GET['type']) : '';
$industryFilter = isset($_GET['industry']) ? trim($_GET['industry']) : '';

// ---- Pagination settings ----
$perPage = 4;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

// ---- Build WHERE clause based on search + filter ----
$conditions = [];

if ($search !== '') {
    $searchEsc = $conn->real_escape_string($search);
    $conditions[] = "title LIKE '%$searchEsc%'";
}

if ($genreFilter !== '') {
    $genreEsc = $conn->real_escape_string($genreFilter);
    $conditions[] = "genre = '$genreEsc'";
}

if ($typeFilter !== '' && in_array($typeFilter, ['Movie', 'Book'])) {
    $typeEsc = $conn->real_escape_string($typeFilter);
    $conditions[] = "type = '$typeEsc'";
}

if ($industryFilter !== '' && in_array($industryFilter, ['Bollywood', 'Hollywood', 'South Indian', 'Other'])) {
    $industryEsc = $conn->real_escape_string($industryFilter);
    $conditions[] = "industry = '$industryEsc'";
}

$whereClause = '';
if (count($conditions) > 0) {
    $whereClause = "WHERE " . implode(" AND ", $conditions);
}

// ---- Count total rows for pagination ----
$countSql = "SELECT COUNT(*) AS total FROM library $whereClause";
$countResult = $conn->query($countSql);
$totalRows = $countResult ? $countResult->fetch_assoc()['total'] : 0;
$totalPages = max(1, ceil($totalRows / $perPage));

// ---- Fetch movies/books for current page ----
$sql = "SELECT * FROM library $whereClause ORDER BY (type = 'Book'), id DESC LIMIT $offset, $perPage";
$result = $conn->query($sql);
$movies = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $movies[] = $row;
    }
}

// ---- Whether we're on the plain default homepage view (no filters at all) ----
$isDefaultView = ($search === '' && $genreFilter === '' && $typeFilter === '' && $industryFilter === '' && $page === 1);

// ---- Top rated (only shown on default view) ----
$topRated = [];
if ($isDefaultView) {
    $topRatedSql = "SELECT * FROM library ORDER BY (type = 'Book'), rating DESC LIMIT 4";
    $topRatedResult = $conn->query($topRatedSql);
    if ($topRatedResult && $topRatedResult->num_rows > 0) {
        while ($row = $topRatedResult->fetch_assoc()) {
            $topRated[] = $row;
        }
    }
}

// ---- Industry sections (Bollywood, Hollywood, South Indian, Other) - only on default view ----
$industrySections = [];
if ($isDefaultView) {
    $industryList = ['Bollywood', 'Hollywood', 'South Indian', 'Other'];
    foreach ($industryList as $ind) {
        $indEsc = $conn->real_escape_string($ind);
        $indSql = "SELECT * FROM library WHERE industry = '$indEsc' ORDER BY id DESC LIMIT 4";
        $indResult = $conn->query($indSql);
        $items = [];
        if ($indResult && $indResult->num_rows > 0) {
            while ($row = $indResult->fetch_assoc()) {
                $items[] = $row;
            }
        }
        if (count($items) > 0) {
            $industrySections[$ind] = $items;
        }
    }
}

// ---- Genre list for filter dropdown ----
$genres = ["Action","Adventure","Comedy","Drama","Fantasy","Horror","Romance","Sci-Fi","Thriller"];

// ---- Build correct poster path whether it's an uploaded file or a pasted URL ----
function posterSrc($poster) {
    if (empty($poster)) return '';
    if (strpos($poster, 'http://') === 0 || strpos($poster, 'https://') === 0) {
        return $poster;
    }
    return 'uploads/' . $poster;
}

// ---- Helper to build pagination links that keep search/genre/type/industry in the URL ----
function buildPageUrl($pageNum, $search, $genreFilter, $typeFilter, $industryFilter) {
    $params = ['page' => $pageNum];
    if ($search !== '') $params['search'] = $search;
    if ($genreFilter !== '') $params['genre'] = $genreFilter;
    if ($typeFilter !== '') $params['type'] = $typeFilter;
    if ($industryFilter !== '') $params['industry'] = $industryFilter;
    return 'index.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>MovieVerse | Home</title>

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

.nav-right{
display:flex;
align-items:center;
gap:16px;
}

.search-box{
display:flex;
align-items:center;
background:rgba(255,255,255,.08);
border:1px solid rgba(255,255,255,.12);
border-radius:30px;
padding:8px 16px;
}

.search-box input{
background:transparent;
border:none;
outline:none;
color:white;
font-size:14px;
width:160px;
}

.search-box input::placeholder{
color:#aaa;
}

.search-box button{
background:none;
border:none;
color:#8b5cf6;
cursor:pointer;
font-size:15px;
}

.add-btn{
background:#8b5cf6;
padding:10px 20px;
border-radius:30px;
text-decoration:none;
color:white;
font-weight:600;
transition:.3s;
white-space:nowrap;
}

.add-btn:hover{
transform:translateY(-3px);
box-shadow:0 0 18px rgba(139,92,246,.5);
}

/* HERO */
.hero{
padding:170px 8% 40px;
text-align:center;
}

.hero h1{
font-size:46px;
margin-bottom:14px;
}

.hero span{
color:#8b5cf6;
}

.hero p{
color:#bdbdbd;
font-size:16px;
margin-bottom:26px;
}

.hero a{
background:#8b5cf6;
padding:14px 32px;
border-radius:30px;
color:white;
text-decoration:none;
font-weight:600;
transition:.3s;
}

.hero a:hover{
transform:translateY(-4px);
box-shadow:0 0 20px rgba(139,92,246,.5);
}

/* FILTER BAR */
.filter-bar{
display:flex;
justify-content:center;
align-items:center;
gap:14px;
flex-wrap:wrap;
padding:0 8% 30px;
}

.filter-bar form{
display:flex;
align-items:center;
gap:10px;
}

.filter-bar label{
color:#bdbdbd;
font-size:14px;
}

.filter-bar select{
background:#171727;
color:white;
border:1px solid rgba(255,255,255,.12);
padding:10px 16px;
border-radius:10px;
font-size:14px;
outline:none;
}

.filter-bar select:focus{
border-color:#8b5cf6;
}

.filter-bar .clear-link{
color:#c4b5fd;
font-size:13px;
text-decoration:none;
}

.filter-bar .clear-link:hover{
text-decoration:underline;
}

/* SECTIONS */
.section{
padding:20px 8% 60px;
}

.section h2{
font-size:26px;
margin-bottom:24px;
border-left:4px solid #8b5cf6;
padding-left:14px;
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

/* PAGINATION */
.pagination{
display:flex;
justify-content:center;
align-items:center;
gap:10px;
margin-top:40px;
flex-wrap:wrap;
}

.pagination a,
.pagination span{
padding:10px 16px;
border-radius:8px;
background:rgba(255,255,255,.06);
color:#ddd;
text-decoration:none;
font-size:14px;
transition:.3s;
border:1px solid rgba(255,255,255,.08);
}

.pagination a:hover{
background:#8b5cf6;
color:white;
border-color:#8b5cf6;
}

.pagination .active{
background:#8b5cf6;
color:white;
border-color:#8b5cf6;
}

.pagination .disabled{
opacity:.4;
pointer-events:none;
}

/* FOOTER */
footer{
text-align:center;
padding:30px;
color:#999;
font-size:14px;
border-top:1px solid rgba(255,255,255,.08);
}

footer span{
color:#8b5cf6;
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
.nav-right{
flex-direction:column;
width:100%;
}
.search-box{
width:100%;
}
.search-box input{
width:100%;
}
.hero{
padding:190px 20px 30px;
}
.hero h1{
font-size:32px;
}
.filter-bar{
padding:0 20px 30px;
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
<a href="index.php?type=Movie">Movies</a>
<a href="index.php?type=Book">Books</a>
<a href="#top-rated">Genres</a>
</nav>

<div class="nav-right">

<form class="search-box" method="GET" action="index.php">
<?php if ($genreFilter !== ''): ?>
<input type="hidden" name="genre" value="<?php echo htmlspecialchars($genreFilter); ?>">
<?php endif; ?>
<?php if ($typeFilter !== ''): ?>
<input type="hidden" name="type" value="<?php echo htmlspecialchars($typeFilter); ?>">
<?php endif; ?>
<?php if ($industryFilter !== ''): ?>
<input type="hidden" name="industry" value="<?php echo htmlspecialchars($industryFilter); ?>">
<?php endif; ?>
<input
type="text"
name="search"
placeholder="Search movies & books..."
value="<?php echo htmlspecialchars($search); ?>">
<button type="submit">
<i class="fa-solid fa-magnifying-glass"></i>
</button>
</form>

<a href="add.php" class="add-btn">
<i class="fa-solid fa-plus"></i>
Add Movie
</a>

<a href="logout.php" style="color:#ddd;text-decoration:none;font-size:14px;display:flex;align-items:center;gap:6px;" title="Logout">
<i class="fa-solid fa-user" style="color:#8b5cf6;"></i>
<?php echo htmlspecialchars($_SESSION['username']); ?>
<i class="fa-solid fa-right-from-bracket"></i>
</a>

</div>

</header>

<section class="hero">
<h1>Unlimited <span>Movies</span> & Books</h1>
<p>Discover • Explore • Enjoy</p>
<a href="#trending">Explore Now</a>
</section>

<div class="filter-bar">
<form method="GET" action="index.php">
<label>Type:</label>

<?php if ($search !== ''): ?>
<input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
<?php endif; ?>
<?php if ($genreFilter !== ''): ?>
<input type="hidden" name="genre" value="<?php echo htmlspecialchars($genreFilter); ?>">
<?php endif; ?>
<?php if ($industryFilter !== ''): ?>
<input type="hidden" name="industry" value="<?php echo htmlspecialchars($industryFilter); ?>">
<?php endif; ?>

<select name="type" onchange="this.form.submit()">
<option value="">All Types</option>
<option value="Movie" <?php echo ($typeFilter === 'Movie') ? 'selected' : ''; ?>>🎬 Movies</option>
<option value="Book" <?php echo ($typeFilter === 'Book') ? 'selected' : ''; ?>>📚 Books</option>
</select>
</form>

<form method="GET" action="index.php">
<label>Filter by Genre:</label>

<?php if ($search !== ''): ?>
<input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
<?php endif; ?>
<?php if ($typeFilter !== ''): ?>
<input type="hidden" name="type" value="<?php echo htmlspecialchars($typeFilter); ?>">
<?php endif; ?>
<?php if ($industryFilter !== ''): ?>
<input type="hidden" name="industry" value="<?php echo htmlspecialchars($industryFilter); ?>">
<?php endif; ?>

<select name="genre" onchange="this.form.submit()">
<option value="">All Genres</option>
<?php foreach ($genres as $g): ?>
<option value="<?php echo $g; ?>" <?php echo ($genreFilter === $g) ? 'selected' : ''; ?>>
<?php echo $g; ?>
</option>
<?php endforeach; ?>
</select>
</form>

<form method="GET" action="index.php">
<label>Industry:</label>

<?php if ($search !== ''): ?>
<input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
<?php endif; ?>
<?php if ($typeFilter !== ''): ?>
<input type="hidden" name="type" value="<?php echo htmlspecialchars($typeFilter); ?>">
<?php endif; ?>
<?php if ($genreFilter !== ''): ?>
<input type="hidden" name="genre" value="<?php echo htmlspecialchars($genreFilter); ?>">
<?php endif; ?>

<select name="industry" onchange="this.form.submit()">
<option value="">All Industries</option>
<option value="Bollywood" <?php echo ($industryFilter === 'Bollywood') ? 'selected' : ''; ?>>Bollywood</option>
<option value="Hollywood" <?php echo ($industryFilter === 'Hollywood') ? 'selected' : ''; ?>>Hollywood</option>
<option value="South Indian" <?php echo ($industryFilter === 'South Indian') ? 'selected' : ''; ?>>South Indian</option>
<option value="Other" <?php echo ($industryFilter === 'Other') ? 'selected' : ''; ?>>Other</option>
</select>
</form>

<?php if ($search !== '' || $genreFilter !== '' || $typeFilter !== '' || $industryFilter !== ''): ?>
<a href="index.php" class="clear-link">
<i class="fa-solid fa-xmark"></i> Clear filters
</a>
<?php endif; ?>
</div>

<section class="section" id="trending">
<h2><i class="fa-solid fa-fire" style="color:#8b5cf6;"></i>
&nbsp;<?php
if ($search !== '' || $genreFilter !== '' || $typeFilter !== '' || $industryFilter !== '') {
    echo 'Results';
} else {
    echo 'Trending';
}
?>
</h2>

<?php if (count($movies) === 0): ?>

<div class="empty-msg">
<?php if ($search !== '' || $genreFilter !== '' || $typeFilter !== '' || $industryFilter !== ''): ?>
No items found matching your search/filter.
<?php else: ?>
No movies or books added yet. Click "Add Movie" to get started!
<?php endif; ?>
</div>

<?php else: ?>

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

<!-- PAGINATION -->
<?php if ($totalPages > 1): ?>
<div class="pagination">

<a class="<?php echo ($page <= 1) ? 'disabled' : ''; ?>"
href="<?php echo buildPageUrl($page - 1, $search, $genreFilter, $typeFilter, $industryFilter); ?>">
<i class="fa-solid fa-chevron-left"></i> Prev
</a>

<?php for ($i = 1; $i <= $totalPages; $i++): ?>
<a class="<?php echo ($i === $page) ? 'active' : ''; ?>"
href="<?php echo buildPageUrl($i, $search, $genreFilter, $typeFilter, $industryFilter); ?>">
<?php echo $i; ?>
</a>
<?php endfor; ?>

<a class="<?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>"
href="<?php echo buildPageUrl($page + 1, $search, $genreFilter, $typeFilter, $industryFilter); ?>">
Next <i class="fa-solid fa-chevron-right"></i>
</a>

</div>
<?php endif; ?>

<?php endif; ?>

</section>

<?php if (count($topRated) > 0): ?>

<section class="section" id="top-rated">
<h2><i class="fa-solid fa-star" style="color:#8b5cf6;"></i>
&nbsp;Top Rated
</h2>

<div class="grid">
<?php foreach ($topRated as $movie): ?>

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

</section>

<?php endif; ?>

<?php foreach ($industrySections as $industryName => $items): ?>

<section class="section">
<h2><i class="fa-solid fa-globe" style="color:#8b5cf6;"></i>
&nbsp;<?php echo htmlspecialchars($industryName); ?>
</h2>

<div class="grid">
<?php foreach ($items as $movie): ?>

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

<a href="index.php?industry=<?php echo urlencode($industryName); ?>" style="display:inline-block;margin-top:20px;color:#c4b5fd;text-decoration:none;font-size:14px;">
See all <?php echo htmlspecialchars($industryName); ?> <i class="fa-solid fa-arrow-right"></i>
</a>

</section>

<?php endforeach; ?>

<footer>
&copy; <?php echo date("Y"); ?> Movie<span>Verse</span>. All rights reserved.
</footer>

</body>
</html>