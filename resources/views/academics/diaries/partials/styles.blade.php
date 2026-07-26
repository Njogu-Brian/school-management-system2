{{-- Digital Diaries UI — settings theme + list primitives --}}
@include('settings.partials.styles')
<style>
    .settings-page .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .settings-page .page-header .header-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }

    .diary-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .diary-stat {
        background: var(--settings-surface, #fff);
        border: 1px solid var(--settings-border);
        border-radius: 14px;
        padding: 0.9rem 1rem;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.04);
        text-decoration: none;
        color: inherit;
        transition: border-color 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
        display: block;
        cursor: pointer;
    }

    .diary-stat:hover {
        border-color: color-mix(in srgb, var(--settings-primary) 35%, var(--settings-border));
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.07);
        transform: translateY(-1px);
        color: inherit;
        text-decoration: none;
    }

    .diary-stat.is-active {
        border-color: var(--settings-primary);
        background: color-mix(in srgb, var(--settings-primary) 8%, #fff);
    }

    .diary-stat .label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--settings-muted);
        margin-bottom: 0.2rem;
    }

    .diary-stat .value {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--settings-text);
        line-height: 1.2;
    }

    .diary-toolbar {
        display: grid;
        grid-template-columns: 1.4fr 1fr 1fr auto auto;
        gap: 0.75rem;
        align-items: end;
    }

    .diary-toolbar .form-label {
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 0.35rem;
    }

    .diary-board {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .diary-row {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.7fr) minmax(0, 1.6fr) auto;
        gap: 1rem;
        align-items: center;
        padding: 1rem 1.15rem;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
        transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
    }

    .diary-row:hover {
        border-color: color-mix(in srgb, var(--settings-primary) 28%, rgba(15, 23, 42, 0.08));
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.07);
        transform: translateY(-1px);
    }

    .diary-student-name {
        font-weight: 700;
        color: var(--settings-text);
        margin: 0;
        font-size: 1rem;
        line-height: 1.3;
    }

    .diary-student-meta {
        color: var(--settings-muted);
        font-size: 0.85rem;
        margin-top: 0.15rem;
    }

    .diary-class-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        background: color-mix(in srgb, var(--settings-primary) 10%, #fff);
        color: var(--settings-primary-dark);
        font-size: 0.82rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .diary-preview {
        min-width: 0;
    }

    .diary-preview-text {
        margin: 0;
        color: var(--settings-text);
        font-size: 0.95rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .diary-preview-meta {
        margin-top: 0.25rem;
        color: var(--settings-muted);
        font-size: 0.8rem;
    }

    .diary-row-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.4rem;
        white-space: nowrap;
    }

    .diary-updated {
        font-size: 0.78rem;
        color: var(--settings-muted);
    }

    .diary-empty {
        text-align: center;
        padding: 3rem 1.5rem;
        color: var(--settings-muted);
    }

    .diary-empty i {
        font-size: 2rem;
        opacity: 0.45;
        display: block;
        margin-bottom: 0.75rem;
    }

    .diary-alert-orphans {
        border-left: 4px solid #f59e0b;
    }

    @media (max-width: 992px) {
        .diary-toolbar {
            grid-template-columns: 1fr 1fr;
        }

        .diary-row {
            grid-template-columns: 1fr auto;
            gap: 0.75rem;
        }

        .diary-row .diary-class,
        .diary-row .diary-preview {
            grid-column: 1 / -1;
        }

        .diary-row-actions {
            grid-column: 2;
            grid-row: 1;
        }
    }

    @media (max-width: 576px) {
        .diary-toolbar {
            grid-template-columns: 1fr;
        }

        .diary-row {
            grid-template-columns: 1fr;
        }

        .diary-row-actions {
            grid-column: 1;
            grid-row: auto;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .diary-stat,
        .diary-row {
            transition: none;
        }

        .diary-stat:hover,
        .diary-row:hover {
            transform: none;
        }
    }
</style>
