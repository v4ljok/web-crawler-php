const API_URL = 'http://localhost/web-crawler-php/backend/index.php?api_key=VALENTIN-IN-LUMAV-2024';
let allProducts = [];

const productList = document.getElementById('product-list');
const categoryTableBody = document.getElementById('category-table-body');
const searchInput = document.getElementById('search');
const loadButton = document.getElementById('load-button');

let isLoading = false;
let categoryChartInstance = null;
let ratingChartInstance = null;