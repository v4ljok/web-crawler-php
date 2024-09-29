const API_URL = 'http://localhost/web-crawler-php/backend/index.php?api_key=VALENTIN-IN-LUMAV-2024';
let allProducts = [];

const productList = document.getElementById('product-list');
const categoryTableBody = document.getElementById('category-table-body');
const searchInput = document.getElementById('search');
const loadButton = document.getElementById('load-button');

let isLoading = false;
let categoryChartInstance = null;
let ratingChartInstance = null;

async function fetchProducts() {
    if (isLoading) return;

    isLoading = true;
    loadButton.disabled = true;

    try {
        document.querySelectorAll('.placeholder-row, .chart-placeholder, .placeholder-text').forEach(element => {
            element.classList.add('skeleton-loading');
        });

        await new Promise(resolve => setTimeout(resolve, 1000));
        
        const response = await fetch(API_URL);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const products = await response.json();

        if (products.error) {
            showError(products.error);
            return;
        }

        allProducts = products;
        
        displayProducts(products);
        displayCategoryTable(products);
        renderCategoryChart(products);
        renderRatingChart(products);
    } catch (error) {
        showError('Failed to fetch products. Please check the API_URL to match your server\'s domain and port.');
        console.error(error);
    } finally {
        document.querySelectorAll('.skeleton-loading').forEach(element => {
            element.classList.remove('skeleton-loading');
        });
        isLoading = false;
    }

    document.getElementById('categoryChart').style.display = 'block';
    document.getElementById('ratingChart').style.display = 'block';
    document.getElementById('categoryChartPlaceholder').style.display = 'none';
    document.getElementById('ratingChartPlaceholder').style.display = 'none'; 
}

function showError(message) {
    alert(message);
}

function searchProducts() {
    const searchTerm = searchInput.value.toLowerCase();
    const filteredProducts = allProducts.filter(product => 
        product.title.toLowerCase().includes(searchTerm) ||
        product.category.toLowerCase().includes(searchTerm)
    );
    displayProducts(filteredProducts);
}

function displayProducts(products) {
    productList.innerHTML = '';

    if (products.length === 0) {
        productList.innerHTML = '<p>No products found.</p>';
        return;
    }

    products.forEach(product => {
        const productElement = document.createElement('div');
        productElement.classList.add('product-item');
        
        const productRating = product.rating && product.rating !== 'No rating' ? `${product.rating} ⭐` : 'No rating available';
        const availability = product.availability ? product.availability : 'Unknown';

        productElement.innerHTML = `
            <h3>${product.title}</h3>
            <p>Price: ${product.price}</p>
            <p>Category: ${product.category}</p>
            <p>Rating: ${productRating}</p>
            <p>Availability: ${availability}</p>
            <a href="${product.link}" target="_blank">View Product</a>
        `;

        productList.appendChild(productElement);
    });
}

function displayCategoryTable(products) {
    categoryTableBody.innerHTML = '';

    const categoryCounts = products.reduce((acc, product) => {
        acc[product.category] = (acc[product.category] || 0) + 1;
        return acc;
    }, {});

    const sortedCategories = Object.entries(categoryCounts).sort((a, b) => b[1] - a[1]);

    sortedCategories.forEach(([category, count]) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${category}</td>
            <td>${count}</td>
        `;
        categoryTableBody.appendChild(row);
    });
}

function renderCategoryChart(products) {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    const categoryCounts = products.reduce((acc, product) => {
        acc[product.category] = (acc[product.category] || 0) + 1;
        return acc;
    }, {});

    if (categoryChartInstance) {
        categoryChartInstance.destroy();
    }

    categoryChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(categoryCounts),
            datasets: [{
                label: 'Number of Products',
                data: Object.values(categoryCounts),
                backgroundColor: 'orange',
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function renderRatingChart(products) {
    const ctx = document.getElementById('ratingChart').getContext('2d');
    const ratingByCategory = products.reduce((acc, product) => {
        if (!acc[product.category]) {
            acc[product.category] = { totalRating: 0, count: 0 };
        }
        acc[product.category].totalRating += parseFloat(product.rating) || 0;
        acc[product.category].count += 1;
        return acc;
    }, {});

    const categories = Object.keys(ratingByCategory);
    const averageRatings = categories.map(cat => (ratingByCategory[cat].totalRating / ratingByCategory[cat].count));

    if (ratingChartInstance) {
        ratingChartInstance.destroy();
    }

    ratingChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: categories,
            datasets: [{
                label: 'Average Rating',
                data: averageRatings,
                backgroundColor: 'lightgreen',
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 5
                }
            }
        }
    });
}