const editButtons = document.querySelectorAll('.btn-edit');
const productForm = document.querySelector('.form-section form');
const productId = document.getElementById('product-id');
const productName = document.getElementById('product-name');
const productPrice = document.getElementById('selling-price');
const productBarcode = document.getElementById('barcode');
const productCategory = document.getElementById('product-category');
const productActive = document.getElementById('product-active');
const productImage = document.getElementById('product-image');
const imagePreview = document.getElementById('imagePreview');
const addProduct = document.getElementById('addProduct');
const updateProduct = document.getElementById('updateProduct');
const cancelEdit = document.getElementById('cancelEdit');
const searchInput = document.getElementById('search-input');

function resetProductForm() {
    productForm.reset();
    productId.value = '';
    updateProduct.disabled = true;
    addProduct.disabled = false;
    cancelEdit.hidden = true;
    imagePreview.innerHTML = '<div class="placeholder"><i class="fas fa-image"></i><p>Aperçu de l’image</p></div>';
}

editButtons.forEach(button => {
    button.addEventListener('click', () => {
        productId.value = button.dataset.id;
        productName.value = button.dataset.name;
        productPrice.value = button.dataset.sellPrice;
        productBarcode.value = button.dataset.barcode;
        productCategory.value = button.dataset.category || '';
        if (!productCategory.value) productCategory.value = productCategory.options[1]?.value || '';
        productActive.checked = button.dataset.active === '1';
        addProduct.disabled = true;
        updateProduct.disabled = false;
        cancelEdit.hidden = false;
        imagePreview.innerHTML = button.dataset.image
            ? `<img src="${button.dataset.image}" alt="" style="width:140px;border-radius:10px;">`
            : '<div class="placeholder"><i class="fas fa-image"></i><p>Aucune image</p></div>';
        productForm.scrollIntoView({behavior: 'smooth', block: 'start'});
    });
});

const requestedEditId = document.body.dataset.editId;
if (requestedEditId && requestedEditId !== '0') {
    Array.from(editButtons).find(button => button.dataset.id === requestedEditId)?.click();
}

cancelEdit.addEventListener('click', resetProductForm);

productImage.addEventListener('change', () => {
    const file = productImage.files[0];
    if (!file) return;
    imagePreview.innerHTML = '';
    const image = document.createElement('img');
    image.src = URL.createObjectURL(file);
    image.alt = 'Aperçu du produit';
    image.style.width = '140px';
    image.style.borderRadius = '10px';
    imagePreview.appendChild(image);
});

searchInput.addEventListener('input', () => {
    const query = searchInput.value.toLocaleLowerCase();
    document.querySelectorAll('#products-tbody tr').forEach(row => {
        row.hidden = !row.textContent.toLocaleLowerCase().includes(query);
    });
});
