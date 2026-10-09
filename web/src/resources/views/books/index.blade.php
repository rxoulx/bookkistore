<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Book Store</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        a, button { margin-right: 8px; }
        .ok { color: green; }
    </style>
</head>
<body>
    <h1>Book Store</h1>
    <p><a href="{{ route('books.create') }}">+ Thêm sách</a></p>

    @if (session('ok'))
        <p class="ok">{{ session('ok') }}</p>
    @endif

    <table>
        <tr>
            <th>Tên sách</th>
            <th>Tác giả</th>
            <th>Giá</th>
            <th>Tồn kho</th>
            <th>Thao tác</th>
        </tr>
        @forelse ($books as $book)
            <tr>
                <td>{{ $book->title }}</td>
                <td>{{ $book->author }}</td>
                <td>{{ number_format($book->price, 0, ',', '.') }}</td>
                <td>{{ $book->stock }}</td>
                <td>
                    <a href="{{ route('books.show', $book) }}">Xem</a>
                    <a href="{{ route('books.edit', $book) }}">Sửa</a>
                    <form method="POST"
                          action="{{ route('books.destroy', $book) }}"
                          style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('Xóa sách này?')">
                            Xóa
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">Chưa có sách.</td></tr>
        @endforelse
    </table>
</body>
</html>
