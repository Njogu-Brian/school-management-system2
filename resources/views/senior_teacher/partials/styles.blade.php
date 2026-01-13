@once
<style>
    .senior-teacher-page {
        --st-primary: #8b5cf6;
        --st-primary-dark: #7c3aed;
        --st-accent: #a78bfa;
        --st-bg: #faf5ff;
        --st-surface: #ffffff;
        --st-border: #e9d5ff;
        --st-text: #1e293b;
        --st-muted: #64748b;
        background: var(--st-bg);
        min-height: 100vh;
        padding: 24px 0;
    }
    
    body.theme-dark .senior-teacher-page {
        --st-bg: #1e1b4b;
        --st-surface: #312e81;
        --st-border: #4c1d95;
        --st-text: #f1f5f9;
        --st-muted: #cbd5e1;
    }
    
    .st-hero {
        background: linear-gradient(135deg, var(--st-primary) 0%, var(--st-accent) 100%);
        color: white;
        border-radius: 18px;
        padding: 28px 32px;
        box-shadow: 0 10px 25px rgba(139, 92, 246, 0.25);
        margin-bottom: 32px;
    }
    
    .st-hero h2 { margin: 0; font-weight: 700; font-size: 2rem; }
    .st-hero .breadcrumb { margin: 10px 0 0; background: transparent; padding: 0; }
    .st-hero .breadcrumb-item, .st-hero .breadcrumb-item a { color: rgba(255,255,255,0.95); text-decoration: none; }
    .st-hero .breadcrumb-item.active { color: rgba(255,255,255,0.75); }
    .st-hero .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,0.6); }
    
    .st-card {
        background: var(--st-surface);
        border: 1px solid var(--st-border);
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        margin-bottom: 24px;
    }
    
    .st-card:hover {
        box-shadow: 0 8px 12px -1px rgba(0, 0, 0, 0.1), 0 4px 6px -1px rgba(0, 0, 0, 0.06);
    }
    
    .st-card-header {
        background: linear-gradient(90deg, rgba(139, 92, 246, 0.08), rgba(167, 139, 250, 0.05));
        border-bottom: 1px solid var(--st-border);
        padding: 18px 24px;
        font-weight: 700;
        color: var(--st-primary);
    }
    
    .st-card-body {
        padding: 24px;
    }
    
    .st-filter-card {
        background: var(--st-surface);
        border: 1px solid var(--st-border);
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    .st-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .st-table thead th {
        background: linear-gradient(90deg, rgba(139, 92, 246, 0.08), rgba(167, 139, 250, 0.05));
        border-bottom: 2px solid var(--st-border);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 16px;
        color: var(--st-primary);
    }
    
    .st-table td {
        padding: 18px 16px;
        border-bottom: 1px solid var(--st-border);
        vertical-align: middle;
    }
    
    .st-table tbody tr {
        transition: background-color 0.2s ease;
    }
    
    .st-table tbody tr:hover {
        background: rgba(139, 92, 246, 0.04);
    }
    
    .st-form-label {
        font-weight: 600;
        color: var(--st-text);
        margin-bottom: 8px;
        display: block;
    }
    
    .st-form-control, .st-form-select {
        width: 100%;
        border: 1px solid var(--st-border);
        border-radius: 10px;
        padding: 10px 14px;
        background: var(--st-surface);
        color: var(--st-text);
        transition: all 0.2s ease;
    }
    
    .st-form-control:focus, .st-form-select:focus {
        border-color: var(--st-primary);
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        outline: none;
    }
    
    .btn-st-primary {
        background: linear-gradient(135deg, var(--st-primary), var(--st-accent));
        border: none;
        color: white;
        font-weight: 600;
        padding: 12px 24px;
        border-radius: 10px;
        box-shadow: 0 4px 6px -1px rgba(139, 92, 246, 0.3);
        transition: all 0.2s ease;
    }
    
    .btn-st-primary:hover {
        background: linear-gradient(135deg, var(--st-primary-dark), var(--st-primary));
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(139, 92, 246, 0.4);
    }
    
    .btn-st-outline {
        background: white;
        border: 2px solid var(--st-primary);
        color: var(--st-primary);
        font-weight: 600;
        padding: 10px 18px;
        border-radius: 10px;
        transition: all 0.2s ease;
    }
    
    .btn-st-outline:hover {
        background: var(--st-primary);
        color: white;
        transform: translateY(-1px);
    }
    
    .btn-st-secondary {
        background: var(--st-muted);
        border: none;
        color: white;
        font-weight: 600;
        padding: 12px 24px;
        border-radius: 10px;
        transition: all 0.2s ease;
    }
    
    .btn-st-secondary:hover {
        background: #475569;
        color: white;
    }
    
    .avatar-circle {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--st-primary), var(--st-accent));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        box-shadow: 0 4px 6px -1px rgba(139, 92, 246, 0.3);
    }
    
    .st-badge {
        padding: 6px 14px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.8rem;
    }
    
    .st-badge-primary {
        background: rgba(139, 92, 246, 0.15);
        color: var(--st-primary);
        border: 1px solid rgba(139, 92, 246, 0.3);
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-state i {
        font-size: 80px;
        color: var(--st-muted);
        opacity: 0.4;
        margin-bottom: 24px;
    }
    
    .st-info-alert {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(96, 165, 250, 0.05));
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 12px;
        padding: 18px 20px;
    }
</style>
@endonce

