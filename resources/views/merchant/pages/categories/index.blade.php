{{-- resources/views/merchant/pages/categories/index.blade.php --}}
@extends('merchant.layouts.main')

@section('title', 'Product Categories')
@section('content-header', 'Product Categories')

@section('css')
<style>
    .stat-card {
        background: #ffffff;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 15px;
        text-align: center;
    }
    
    .stat-card h3 {
        font-size: 28px;
        margin: 0;
        color: #212529;
        font-weight: 600;
    }
    
    .stat-card p {
        margin: 5px 0 0;
        color: #6c757d;
        font-size: 13px;
    }
    
    .copy-btn {
        cursor: pointer;
        padding: 3px 8px;
        font-size: 11px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 3px;
        transition: all 0.2s;
        white-space: nowrap;
    }
    
    .copy-btn:hover {
        background: #007bff;
        border-color: #007bff;
        color: white;
    }
    
    .category-id-text {
        font-family: 'Courier New', monospace;
        font-size: 12px;
        color: #495057;
        background: #f8f9fa;
        padding: 2px 6px;
        border-radius: 3px;
        display: inline-block;
    }
    
    .table td {
        vertical-align: middle;
        padding: 10px 8px;
    }
    
    .btn-download {
        background: #28a745;
        border-color: #28a745;
        color: white;
    }
    
    .btn-download:hover {
        background: #218838;
        border-color: #1e7e34;
        color: white;
    }
    
    .path-text {
        font-size: 13px;
    }
    
    .cell-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }
    
    .id-cell {
        mso-number-format: "\@";
    }
</style>
@endsection

@section('content')

<!-- Statistics Row -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <h3>{{ count($categories) }}</h3>
            <p>Total Categories</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <h3>{{ count(array_filter($categories, function($cat) { return empty($cat['parent']); })) }}</h3>
            <p>Parent Categories</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <h3>{{ count(array_filter($categories, function($cat) { return !empty($cat['parent']); })) }}</h3>
            <p>Subcategories</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <h3 id="maxDepth">0</h3>
            <p>Max Depth</p>
        </div>
    </div>
</div>

<!-- Search and Actions Bar -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search categories...">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-right">
                <button type="button" id="downloadCsv" class="btn btn-download mr-2">
                    <i class="fas fa-download"></i> Download CSV
                </button>
                <button type="button" id="refreshCategories" class="btn btn-primary">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Categories Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Categories List</h3>
        <div class="card-tools">
            <small class="text-muted">Click copy buttons to copy values for CSV import</small>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th width="40">#</th>
                        <th width="35%">Category Path (Use in CSV)</th>
                        <th width="35%">Internal Name</th>
                        <th width="30%">Category ID</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @php $maxDepth = 0; @endphp
                    @foreach($categories as $index => $cat)
                        @php
                            $depth = substr_count($cat['csv_name'] ?? $cat['name'], ' > ');
                            if ($depth > $maxDepth) $maxDepth = $depth;
                            // Force ID as string
                            $rawId = (string) $cat['id'];
                            if (strpos($rawId, 'E') !== false || strpos($rawId, 'e') !== false) {
                                $rawId = number_format((float) $rawId, 0, '', '');
                            }
                        @endphp
                        <tr data-search="{{ strtolower(($cat['csv_name'] ?? $cat['name']) . ' ' . ($cat['internalName'] ?? '') . ' ' . $rawId) }}">
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                <div class="cell-content">
                                    <span class="path-text">{{ $cat['csv_name'] ?? $cat['name'] }}</span>
                                    <button class="copy-btn" onclick="copyText('{{ addslashes($cat['csv_name'] ?? $cat['name']) }}', this)">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                            </td>
                            <td>
                                <div class="cell-content">
                                    <code>{{ $cat['internalName'] ?? '—' }}</code>
                                    @if(!empty($cat['internalName']))
                                    <button class="copy-btn" onclick="copyText('{{ $cat['internalName'] }}', this)">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                            <td class="id-cell">
                                <div class="cell-content">
                                    <span class="category-id-text">{{ $rawId }}</span>
                                    <button class="copy-btn" onclick="copyText('{{ $rawId }}', this)">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <span id="visibleCount">{{ count($categories) }}</span> categories displayed
    </div>
</div>

<script>
    // Set max depth
    document.getElementById('maxDepth').textContent = '{{ $maxDepth }}';
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const clearBtn = document.getElementById('clearSearch');
    const tbody = document.getElementById('tableBody');
    const visibleCountSpan = document.getElementById('visibleCount');
    
    function filterTable() {
        const term = searchInput.value.toLowerCase().trim();
        const rows = tbody.querySelectorAll('tr');
        let visible = 0;
        
        rows.forEach(row => {
            const data = row.getAttribute('data-search') || '';
            if (term === '' || data.includes(term)) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });
        
        visibleCountSpan.textContent = visible;
    }
    
    searchInput.addEventListener('keyup', filterTable);
    clearBtn.addEventListener('click', function() {
        searchInput.value = '';
        filterTable();
    });
    
    // Copy function
    function copyText(text, btn) {
        navigator.clipboard.writeText(text).then(() => {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            btn.style.background = '#28a745';
            btn.style.color = 'white';
            setTimeout(() => {
                btn.innerHTML = original;
                btn.style.background = '';
                btn.style.color = '';
            }, 1000);
        });
    }
    
    // Download CSV (without Category ID)
    document.getElementById('downloadCsv')?.addEventListener('click', function() {
        const rows = tbody.querySelectorAll('tr');
        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
        
        if (visibleRows.length === 0) {
            alert('No categories to download');
            return;
        }
        
        // CSV headers - only Category Path and Internal Name (NO ID)
        let csv = '\uFEFF"Category Path (Use in CSV)","Internal Name"\n';
        
        visibleRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 4) {
                // Get category path from second cell
                const pathSpan = cells[1].querySelector('.path-text');
                let path = pathSpan ? pathSpan.textContent.trim() : '';
                path = path.replace(/"/g, '""');
                
                // Get internal name from third cell
                const internalCode = cells[2].querySelector('code');
                let internalName = internalCode ? internalCode.textContent.trim() : '';
                internalName = internalName.replace(/"/g, '""');
                
                // Add to CSV (NO ID column)
                csv += `"${path}","${internalName}"\n`;
            }
        });
        
        // Create download
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'categories_' + new Date().toISOString().slice(0,19).replace(/:/g, '-') + '.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    });
    
    // Refresh
    document.getElementById('refreshCategories')?.addEventListener('click', function() {
        if (!confirm('Refresh categories?')) return;
        const btn = this;
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch('{{ route("merchant.categories.refresh") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) location.reload();
            else alert('Failed');
            btn.disabled = false;
            btn.innerHTML = original;
        })
        .catch(() => {
            alert('Error');
            btn.disabled = false;
            btn.innerHTML = original;
        });
    });
</script>
@endsection