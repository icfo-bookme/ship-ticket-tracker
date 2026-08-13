     <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 ">
         <div id="statusFilter" data-status="{{ $status }}" class="hidden"></div>
         <div class="flex items-center justify-between py-6">
             <h2 class="font-semibold text-xl text-gray-800  leading-tight">
                 Ship Ticket Sales ({{ $status }} )
             </h2>
         </div>
         @if (session('success'))
             <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                 <p>{{ session('success') }}</p>
             </div>
         @endif
         <button id="printAllBtn" class="hidden px-4 py-2 bg-red-600 text-white rounded flex items-center gap-2">
             <i class="fa-solid fa-print"></i>
             Print All Tickets
         </button>

         <div class="mt-6 mb-4 grid grid-cols-4 gap-10">
             <div class="">
                 <label for="companyFilter" class="block text-sm font-medium text-gray-700">Filter by Source
                     Company</label>
                 <select id="companyFilter"
                     class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                     <!-- Options will be populated dynamically via JS -->
                 </select>
             </div>
             <div class="">
                 <label for="shipFilter" class="block text-sm font-medium text-gray-700">Filter by Ship</label>
                 <select id="shipFilter"
                     class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                     <!-- Options will be populated dynamically via JS -->
                 </select>
             </div>

             <div class="flex-1">
                 <label for="journeyDateFilter" class="block text-sm font-medium text-gray-700">Filter by Journey
                     Date</label>
                 <input type="date" id="journeyDateFilter"
                     class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
             </div>

             <div class="flex items-end">
                 <button id="clearFilters"
                     class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                     Clear Filters
                 </button>
             </div>
         </div>

         <!-- Loader -->
         <div id="loader" class="text-center my-4 min-h-[100vh]">
             <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
             <p class="mt-2 text-gray-600">Loading data...</p>
         </div>

         <!-- Sales Table -->
         <div class="overflow-x-auto max-w-[100%]">
             <table id="salesTable" class="min-w-full border border-gray-300 hidden">
                 <thead class="bg-[#003366] text-white">
                     <tr>
                         <th class="border px-4 py-2">ID</th>
                         <th class="border px-4 py-2">Customer Name</th>
                         <th class="border px-4 py-2">Mobile</th>
                         <th class="border px-4 py-2">WhatsApp</th>
                         <th class="border px-4 py-2">Ship Name</th>
                         @if ($status == 'shipment_id_entered')
                             <th class="border px-4 py-2">Shipment Id</th>
                         @endif

                         @if ($status == 'pending')
                             <th class="border px-4 py-2">Total Received Amount</th>
                         @endif

                         @if ($status == 'pending')
                             <th class="border px-4 py-2">Payment Methods</th>
                         @endif

                         {{-- <th class="border px-4 py-2">Journey Date</th>
                         <th class="border px-4 py-2">Ticket Fee</th>
                         <th class="border px-4 py-2">Resource Company</th>
                         <th class="border px-4 py-2">Status</th> --}}
                         <th class="border px-4 py-2">Remark 1</th>
                         <th class="border px-4 py-2">Remark 2</th>
                         <th class="border px-4 py-2">Action</th>
                     </tr>
                 </thead>
                 <tbody id="salesBody"></tbody>
             </table>
         </div>

     </div>
     </div>

     <script src="{{ asset('js/panding-sell.js') }}"></script>
