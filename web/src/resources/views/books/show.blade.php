<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $book->title }}</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; }
    </style>
</head>
<body>
    <h1>{{ $book->title }}</h1>
    <p><b>Tác giả:</b> {{ $book->author }}</p>
    <p><b>Giá:</b> {{ number_format($book->price, 0, ',', '.') }}</p>
    <p><b>Tồn kho:</b> {{ $book->stock }}</p>
    <p><b>Mô tả:</b> {{ $book->description }}</p>

    <a href="{{ route('books.edit', $book) }}">Sửa</a>
    <a href="{{ route('books.index') }}">Về danh sách</a>
</body>
</html>
