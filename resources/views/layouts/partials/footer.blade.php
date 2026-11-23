<!-- Footer Component -->
<footer class="hk-footer">
    <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2">
        <div class="text-center text-sm-start">
            <span class="fw-semibold text-dark">UdhaarHisab SaaS</span> &copy; {{ date('Y') }} — Multi-Tenant Udhaar Management.
        </div>
        <div class="d-flex align-items-center gap-3 text-muted">
            <span class="badge bg-success-subtle text-success border border-success-subtle">
                <i class="bi bi-shield-lock-fill me-1"></i> Multi-Tenant Isolated
            </span>
            <span>Server Time: {{ now()->format('H:i:s T') }}</span>
        </div>
    </div>
</footer>

