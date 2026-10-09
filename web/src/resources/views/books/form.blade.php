<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $book->exists ? 'Sửa' : 'Thêm' }} sách</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; }
        label { display: block; margin-top: 14px; }
        input, textarea { display: block; width: 100%; padding: 8px; }
        button { margin-top: 18px; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>{{ $book->exists ? 'Sửa' : 'Thêm' }} sách</h1>

    @if ($errors->any())
        <ul class="error">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST"
          action="{{ $book->exists
              ? route('books.update', $book)
              : route('books.store') }}">
        @csrf
        @if ($book->exists)
            @method('PUT')
        @endif

        <label>Tên sách
            <input name="title" required
                   value="{{ old('title', $book->title) }}">
        </label>

        <label>Tác giả
            <input name="author" required
                   value="{{ old('author', $book->author) }}">
        </label>

        <label>Giá
            <input name="price" type="number" step="0.01" min="0" required
                   value="{{ old('price', $book->price) }}">
        </label>

        <label>Tồn kho
            <input name="stock" type="number" min="0" required
                   value="{{ old('stock', $book->stock) }}">
        </label>

        <label>Mô tả
            <textarea name="description">{{ old('description', $book->description) }}</textarea>
        </label>

        <button type="submit">Lưu</button>
    </form>

    <p><a href="{{ route('books.index') }}">Về danh sách</a></p>
</body>
</html>
