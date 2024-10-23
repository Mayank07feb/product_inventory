<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Product Inventory</title>
</head>

<body>
    <div class="container mt-5">
        <h2>Product Inventory</h2>
        <form id="productForm" method="POST" action="/products">
            @csrf
            <input type="hidden" id="productIndex" name="index">
            <div class="form-group">
                <label for="productName">Product Name:</label>
                <input type="text" class="form-control" id="productName" name="product_name" required>
            </div>
            <div class="form-group">
                <label for="quantity">Quantity in Stock:</label>
                <input type="number" class="form-control" id="quantity" name="quantity" required>
            </div>
            <div class="form-group">
                <label for="price">Price per Item:</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>

        <h3 class="mt-4">Submitted Products</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Quantity in Stock</th>
                    <th>Price per Item</th>
                    <th>Datetime Submitted</th>
                    <th>Total Value</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="productTableBody">
                @foreach ($products as $index => $product)
                    <tr data-index="{{ $index }}">
                        <td>{{ $product['product_name'] }}</td>
                        <td>{{ $product['quantity'] }}</td>
                        <td>{{ $product['price'] }}</td>
                        <td>{{ $product['created_at'] }}</td>
                        <td>{{ number_format($product['quantity'] * $product['price'], 2) }}</td>
                        <td>
                            <button class="btn btn-warning edit-button">Edit</button>
                            <button class="btn btn-danger delete-button">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4">Total:</th>
                    <th id="totalSum">
                        {{ number_format(
                            array_sum(
                                array_map(function ($product) {
                                    return $product['quantity'] * $product['price'];
                                }, $products),
                            ),
                            2,
                        ) }}
                    </th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            function updateTotalSum() {
                let total = 0;
                $('#productTableBody tr').each(function() {
                    const quantity = parseFloat($(this).find('td:eq(1)').text());
                    const price = parseFloat($(this).find('td:eq(2)').text());
                    const totalValue = quantity * price;
                    $(this).find('td:eq(4)').text(totalValue.toFixed(2));
                    total += totalValue;
                });
                $('#totalSum').text(total.toFixed(2));
            }

            // Handle form submission via AJAX
            $('#productForm').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                const productIndex = $('#productIndex').val();

                let url = productIndex ? `/products/${productIndex}` : '/products';
                let method = productIndex ? 'PUT' : 'POST';

                $.ajax({
                    url: url,
                    type: method,
                    data: formData,
                    success: function(data) {
                        $('#productTableBody').empty();
                        const productsArray = Array.isArray(data) ? data : Object.values(data);

                        productsArray.forEach(function(product, index) {
                            const totalValue = product.quantity * product
                            .price; // Calculate total value
                            $('#productTableBody').append(`
                                <tr data-index="${index}">
                                    <td>${product.product_name}</td>
                                    <td>${product.quantity}</td>
                                    <td>${product.price}</td>
                                    <td>${product.created_at}</td>
                                    <td>${totalValue.toFixed(2)}</td>
                                    <td>
                                        <button class="btn btn-warning edit-button">Edit</button>
                                        <button class="btn btn-danger delete-button">Delete</button>
                                    </td>
                                </tr>
                            `);
                        });
                        updateTotalSum();
                        $('#productForm')[0].reset();
                        $('#productIndex').val('');
                    },
                    error: function(xhr) {
                        alert('Error submitting the product.');
                    }
                });
            });

            // Handle delete button click event
            $(document).on('click', '.delete-button', function() {
                const row = $(this).closest('tr');
                const productIndex = row.data('index');

                $.ajax({
                    url: `/products/${productIndex}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        $('#productTableBody').empty();
                        const productsArray = Array.isArray(data) ? data : Object.values(data);

                        productsArray.forEach(function(product, index) {
                            const totalValue = product.quantity * product
                            .price; // Calculate total value
                            $('#productTableBody').append(`
                                <tr data-index="${index}">
                                    <td>${product.product_name}</td>
                                    <td>${product.quantity}</td>
                                    <td>${product.price}</td>
                                    <td>${product.created_at}</td>
                                    <td>${totalValue.toFixed(2)}</td>
                                    <td>
                                        <button class="btn btn-warning edit-button">Edit</button>
                                        <button class="btn btn-danger delete-button">Delete</button>
                                    </td>
                                </tr>
                            `);
                        });
                        updateTotalSum();
                    },
                    error: function(xhr) {
                        alert('Error deleting the product.');
                    }
                });
            });

            // Handle edit button click event
            $(document).on('click', '.edit-button', function() {
                const row = $(this).closest('tr');
                const productIndex = row.data('index');
                const productName = row.find('td:eq(0)').text();
                const quantity = row.find('td:eq(1)').text();
                const price = row.find('td:eq(2)').text();

                $('#productName').val(productName);
                $('#quantity').val(quantity);
                $('#price').val(price);
                $('#productIndex').val(productIndex); // Set the index for editing

                // Remove the default submit handler and bind the edit handler
                $('#productForm').off('submit').on('submit', function(e) {
                    e.preventDefault();
                    const formData = $(this).serialize();

                    $.ajax({
                        url: `/products/${productIndex}`,
                        type: 'PUT',
                        data: formData,
                        success: function(data) {
                            $('#productTableBody').empty();
                            const productsArray = Array.isArray(data) ? data : Object
                                .values(data);

                            productsArray.forEach(function(product, index) {
                                const totalValue = product.quantity * product
                                    .price; // Calculate total value
                                $('#productTableBody').append(`
                                    <tr data-index="${index}">
                                        <td>${product.product_name}</td>
                                        <td>${product.quantity}</td>
                                        <td>${product.price}</td>
                                        <td>${product.created_at}</td>
                                        <td>${totalValue.toFixed(2)}</td>
                                        <td>
                                            <button class="btn btn-warning edit-button">Edit</button>
                                            <button class="btn btn-danger delete-button">Delete</button>
                                        </td>
                                    </tr>
                                `);
                            });
                            updateTotalSum();
                            $('#productForm')[0].reset();
                            $('#productIndex').val(''); // Reset the index after editing
                            // Rebind the original submit handler
                            $('#productForm').off('submit').on('submit', function(e) {
                                e.preventDefault();
                                const formData = $(this).serialize();

                                $.ajax({
                                    url: '/products',
                                    type: 'POST',
                                    data: formData,
                                    success: function(data) {
                                        $('#productTableBody')
                                            .empty();
                                        const productsArray = Array
                                            .isArray(data) ? data :
                                            Object.values(data);

                                        productsArray.forEach(
                                            function(product,
                                                index) {
                                                const
                                                    totalValue =
                                                    product
                                                    .quantity *
                                                    product
                                                    .price; // Calculate total value
                                                $('#productTableBody')
                                                    .append(`
                                                <tr data-index="${index}">
                                                    <td>${product.product_name}</td>
                                                    <td>${product.quantity}</td>
                                                    <td>${product.price}</td>
                                                    <td>${product.created_at}</td>
                                                    <td>${totalValue.toFixed(2)}</td>
                                                    <td>
                                                        <button class="btn btn-warning edit-button">Edit</button>
                                                        <button class="btn btn-danger delete-button">Delete</button>
                                                    </td>
                                                </tr>
                                            `);
                                            });
                                        updateTotalSum();
                                        $('#productForm')[0]
                                    .reset();
                                        $('#productIndex').val('');
                                    },
                                    error: function(xhr) {
                                        alert(
                                            'Error submitting the product.');
                                    }
                                });
                            });
                        },
                        error: function(xhr) {
                            alert('Error updating the product.');
                        }
                    });
                });
            });
        });
    </script>
</body>

</html>
