<?php
session_start();
require_once 'db_connect.php';

$product_query = "SELECT p.*, c.name AS category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  ORDER BY p.id DESC LIMIT 6"; 
$products = $connection->query($product_query)->fetch_all(MYSQLI_ASSOC);
$category_query = "SELECT * FROM categories WHERE type = 'product'";
$categories = $connection->query($category_query)->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sri Lankan Artisans - Cultural Showcase</title>
  <link href="images/TH_logo_br.png" rel="icon">
  <link rel="stylesheet" href="css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
  <style>
  .search-bar {
    display: flex;
    justify-content: center;
    margin: 20px 0;
    animation: fadeInUp 1s ease 0.6s;
    animation-fill-mode: backwards;
  }

  .search-bar input {
    width: 60%;
    max-width: 400px;
    padding: 12px 20px;
    border: none;
    border-radius: 25px 0 0 25px;
    font-size: 16px;
    outline: none;
    background: #FFF;
    color: #333;
  }

  .search-bar button {
    padding: 12px 25px;
    border: none;
    border-radius: 0 25px 25px 0;
    background: #00A859;
    color: #FFF;
    font-weight: 400;
    cursor: pointer;
    transition: background 0.3s;
  }

  .search-bar button:hover {
    background: #008040;
  }


  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }


  .search-bar form {
    display: flex;
    width: 100%;
    justify-content: center;
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .navbar {
      padding: 20px;
    }

    .nav-links {
      display: none;
      flex-direction: column;
      position: absolute;
      top: 70px;
      left: 0;
      width: 100%;
      background: #8C2F39;
      /* Maroon */
      padding: 20px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .nav-links.active {
      display: flex;
    }

    .hamburger {
      display: block;
    }

    .hero-content h1 {
      font-size: 32px;
    }

    .hero-content p {
      font-size: 16px;
    }

    .search-bar {
      flex-direction: column;
      gap: 15px;
      width: 90%;
      margin: 20px;

    }

    .search-bar input {
      width: 100%;
      border-radius: 25px;
      padding: 16px 20px;
      font-size: 16px;
    }

    .search-bar button {
      width: 100%;
      max-width: 100px;
      border-radius: 25px;
      font-size: 16px;
      margin-left: 10px;

    }

    .category-grid {
      grid-template-columns: repeat(2, 1fr);
    }

    .product-grid {
      grid-template-columns: 1fr;
    }

    .contact-content {
      flex-direction: column;
      text-align: center;
    }

    .contact-info {
      text-align: center;
    }
  }

  @media (max-width: 480px) {
    .category-grid {
      grid-template-columns: 1fr;
    }

    .search-bar {
      flex-direction: column;
      gap: 10px;
      align-items: center;
    }

    .search-bar input {
      width: 100%;
      border-radius: 25px;
    }

    .search-bar button {
      border-radius: 25px;
    }
  }
  .hero {
  height: 100vh;
  background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('images/hero.jpg');
  background-size: cover;
  background-position: center;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: #FFF;
}

  </style>
  <style>
  #google_translate_element {
    margin: 10px 0;
  }
  </style>


</head>

<body>
  <!-- Header Section -->
  <header class="hero">
    <nav class="navbar">
      <div class="logo">Thambapanni Heritage</div>
      <ul class="nav-links">
        <li><a href="#home">Home</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="#categories">Categories</a></li>
        <li><a href="#products">Products</a></li>
        <li><a href="#contact">Contact</a></li>
        <li><a href="shop.php">Shop</a></li>
        <li><a href="https://deepskyblue-lobster-260457.hostingersite.com/Learn/">Learn</a></li>
        <li><a href="Seller/index.html" style="color: #FFC107;"><b>Become a Seller</b></a></li>
        <div class="gtranslate_wrapper"></div>
        <script>
        window.gtranslateSettings = {
          "default_language": "en",
          "languages": ["en", "si", "ta"],
          "wrapper_selector": ".gtranslate_wrapper",
          "flag_size": 24,
          "switcher_horizontal_position": "inline",
          "flag_style": "3d"
        }
        </script>
        <script src="https://cdn.gtranslate.net/widgets/latest/dwf.js" defer></script>
        <li><a href="customer_login.php"><b>Sign in</b></a></li>
        <li><a href="customer_registration.php" class="btn2">Register</a></li>
      </ul>
      <div class="hamburger">☰</div>
    </nav>
    <div class="hero-content">
      <h1>Discover Sri Lanka's Cultural Treasures</h1>
      <p>Connecting artisans to the world through a modern platform.</p>
      <?php if (isset($_GET['message'])) { ?>
      <p><?php echo htmlspecialchars($_GET['message']); ?></p>
      <?php } ?>
      <div class="search-bar">
        <form action="search.php" method="GET">
          <input type="text" name="search" placeholder="Search for products or artisans..." id="search-input">
          <button type="submit" id="search-btn">Search</button>
        </form>
      </div>
      <a href="#about" class="btn">Learn More</a>
    </div>
  </header>

  <!-- About Section -->
  <section id="about" class="about">
    <div class="container">
      <h2>About Our Mission</h2>
      <p>Sri Lanka is globally recognized for its rich cultural diversity and traditional craftsmanship, including
        handicrafts, paintings, textiles, and sculptures. However, many talented artisans lack access to modern
        platforms to display their products and reach wider audiences. This project addresses this gap by offering a
        dedicated e-commerce and showcase platform tailored specifically for Sri Lankan cultural products.</p>
      <a href="Registration.php" class="btn secondary">Join Us</a>
    </div>
  </section>

 <!-- Categories Section -->
<section id="categories" class="categories">
  <div class="container">
    <h2>Explore Categories</h2>
    <div class="category-grid">
      <div class="category-item">
        <a href="https://deepskyblue-lobster-260457.hostingersite.com/shop.php?category=6">
          <img src="images/handicrafts.jpg" alt="Handicrafts">
          <h3>Handicrafts</h3>
        </a>
      </div>
      <div class="category-item">
        <a href="https://deepskyblue-lobster-260457.hostingersite.com/shop.php?category=8">
          <img src="images/paintings.jpg" alt="Paintings">
          <h3>Paintings</h3>
        </a>
      </div>
      <div class="category-item">
        <a href="https://deepskyblue-lobster-260457.hostingersite.com/shop.php?category=7">
          <img src="images/textiles.jpg" alt="Textiles">
          <h3>Textiles</h3>
        </a>
      </div>
      <div class="category-item">
        <a href="https://deepskyblue-lobster-260457.hostingersite.com/shop.php?category=9">
          <img src="images/sculptures.jpg" alt="Sculptures">
          <h3>Sculptures</h3>
        </a>
      </div>
      <div class="category-item">
        <a href="https://deepskyblue-lobster-260457.hostingersite.com/shop.php?category=10">
          <img src="images/ceramic.jpg" alt="Ceramic">
          <h3>Ceramic</h3>
        </a>
      </div>
    </div>
  </div>
</section>

  <!-- Latest Products Section -->
  <section id="products" class="products">
    <div class="container">
      <h2>Latest Products</h2>
      <div class="product-grid">
        <?php if (empty($products)) { ?>
        <p>No products available.</p>
        <?php } else { ?>
        <?php foreach ($products as $product) { ?>
        <div class="product-item">
          <?php if (!empty($product['image'])) { ?>
          <img src="<?php echo htmlspecialchars($product['image']); ?>"
            alt="<?php echo htmlspecialchars($product['name']); ?>">
          <?php } else { ?>
          <img src="images/placeholder.jpg" alt="No Image">
          <?php } ?>
          <h3><?php echo htmlspecialchars($product['name']); ?></h3>
          <p>LKR <?php echo number_format($product['price'], 2); ?></p>
          <br>
          <a href="shop.php?product_id=<?php echo $product['id']; ?>" class="btn">View Details</a>
        </div>
        <?php } ?>
        <?php } ?>
      </div>
      <div class="view-all">
        <a href="shop.php" class="btn secondary">View All Products</a>
      </div>
    </div>
  </section>

  <!-- Contact Section -->
  <section id="contact" class="contact">
    <div class="container">
      <h2>Contact Us</h2>
      <div class="contact-content">
        <div class="contact-info">
          <p>Email: info@thambapanniheritage.lk</p>
          <p>Phone: +94 72 850 6970</p>
          <p>Address: Kandy, Sri Lanka</p>
        </div>
        <form class="contact-form">
          <input type="text" placeholder="Your Name" required>
          <input type="email" placeholder="Your Email" required>
          <textarea placeholder="Your Message" rows="5" required></textarea>
          <button type="submit" class="btn">Send Message</button>
        </form>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <section class="footer" style="margin-top: 0px;">
    <div class="footer-box">
      <a href="#" class="logo"></i>Thambapanni Heritage</a>
      <p>Kandy,50th Street,4th <br>Floor</p>
      <div class="social">
        <a href="#"><i class='bx bxl-facebook'></i></a>
        <a href="#"><i class='bx bxl-twitter'></i></a>
        <a href="#"><i class='bx bxl-instagram'></i></a>
        <a href="#"><i class='bx bxl-youtube'></i></a>
      </div>

    </div>
    <div class="footer-box">
      <h2>Our Products</h2>
      <a href="https://deepskyblue-lobster-260457.hostingersite.com/shop.php?category=6">Handcrafted Jewelry</a>
      <a href="https://deepskyblue-lobster-260457.hostingersite.com/shop.php?category=7">Traditional Textiles & Batiks</a>
      <a href="https://deepskyblue-lobster-260457.hostingersite.com/shop.php?category=8">Sri Lankan Paintings</a>
      <a href="https://deepskyblue-lobster-260457.hostingersite.com/shop.php?category=9">Wooden Sculptures</a>
      <a href="https://deepskyblue-lobster-260457.hostingersite.com/shop.php?category=9">Ceramic Pottery</a>

    </div>
    <div class="footer-box">
      <h2>Sell Your Gigs</h2>
      <a href="#">Become a Seller</a>
      <a href="#">Freelance Services</a>
      <a href="#">How It Works</a>
      <a href="#">Payment Methods</a>
    </div>
    <div class="footer-box">
      <h2>Customer Support</h2>
      <a href="#">FAQs</a>
      <a href="#">Order Tracking</a>
      <a href="#">Refund Policy</a>
      <a href="#">Contact Us</a>
    </div>
    <div class="footer-box">
      <h2>Useful Links</h2>
      <a href="#">About Us</a>
      <a href="#">Privacy Policy</a>
      <a href="#">Terms & Conditions</a>
      <a href="#">Blog</a>
    </div>
    <div class="footer-box">
      <h2>News letter</h2>
      <p>Get 10% Discount with <br>Email Newsletter</p>
      <form action="">
        <i class='bx bxs-envelope'></i>
        <input type="email" name="" id="" placeholder="Enter your Email">
        <i class='bx bx-arrow-back bx-rotate-180'></i>

      </form>
    </div>
  </section>
  <!--Copyright-->
  <div class="copyright">
    <p>
      &#169; Thambapanni Heritage All Right Reserved.
    </p>
  </div>
<!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/67c33d609b9d8e190efa91bd/1il9afgrc';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->

  <script src="js/script.js"></script>
</body>

</html>