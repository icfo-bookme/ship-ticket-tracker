@if (session('success'))
    <div class="mb-4 text-green-700 bg-green-100 p-3 rounded">
        {{ session('success') }}
    </div>
@endif

<form action="{{ route('pdf.upload') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <label class="block mb-2 font-medium text-gray-700">
        Upload PDF Files
    </label>

    <input type="file" name="pdfs[]" multiple accept="application/pdf"
        class="block w-full border border-gray-300 rounded-md p-2">

    @error('pdfs')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
    @enderror

    @error('pdfs.*')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
    @enderror

    <button type="submit" class="mt-4 px-4 py-2 bg-blue-950 text-white rounded-md hover:bg-blue-800">
        Upload PDFs
    </button>
</form>
