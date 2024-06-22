<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopify Products</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div class="container mt-5">
        <h1>Shopify Products</h1>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <h2>Create New Product</h2>
        <form action="{{ url('/create-product') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="title">Product Title</label>
                <input type="text" name="title" id="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="body_html">Product Description</label>
                <textarea name="body_html" id="body_html" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label for="price">Product Price</label>
                <input type="number" step="0.01" name="price" id="price" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="image">Product Image URL</label>
                <input type="url" name="image" id="image" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Create Product</button>
        </form>

        <h2 class="mt-5">Product List</h2>
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Title</th>
                    <th scope="col">Description</th>
                    <th scope="col">Price</th>
                    <th scope="col">Image</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $product)
                    <tr data-product-id="{{ $product['id'] }}">
                        <th scope="row">{{ $product['id'] }}</th>
                        <td>
                            <input type="text" name="title" value="{{ $product['title'] }}" class="form-control">
                        </td>
                        <td>
                            <textarea name="body_html" class="form-control">{{ $product['body_html'] }}</textarea>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="price" value="{{ $product['variants'][0]['price'] }}" class="form-control">
                        </td>
                        <td>
                            @if(isset($product['image']))
                                <img src="{{ $product['image']['src'] }}" alt="{{ $product['title'] }}" width="50">
                            @endif
                        </td>
                        <td>
                            <form action="{{ url('/delete-product/' . $product['id']) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-success btn-sm" onclick="updateProduct({{ $product['id'] }})">
                                <i class="fas fa-save"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <script>
        function updateProduct(id) {
            let row = document.querySelector(`tr[data-product-id="${id}"]`);
            let title = row.querySelector('input[name="title"]').value;
            let body_html = row.querySelector('textarea[name="body_html"]').value;
            let price = row.querySelector('input[name="price"]').value;

            fetch(`/update-product/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    title: title,
                    body_html: body_html,
                    price: price
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Product updated successfully!');
                } else {
                    alert('Failed to update product.');
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
