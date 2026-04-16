<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/4.0.0/jquery.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/script.min.js?h=76fb943b07981bddcd684084e3798cff"></script>
<script>
const table = $('#AppConfigData').DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50, 75, 100],
    ordering: true,
    searching: true,
    autoWidth: false, // IMPORTANT
    scrollX: false, // IMPORTANT (prevents forced horizontal scroll)
    dom: '<"row align-items-center mb-2"<"col-md-6"l>>' +
        'rt' +
        '<"row align-items-center mt-2"<"col-md-6"i><"col-md-6 d-flex justify-content-end"p>>'
});
//search
let timer;
$('input[name="searchAppConfig"]').on('input', function() {
    clearTimeout(timer);
    const value = this.value;

    timer = setTimeout(() => {
        table.search(value).draw();
    }, 150);
});
//filtering
table.columns().every(function() {
    var that = this;
    $('input', this.header()).on('keyup change', function() {
        that.search(this.value).draw();
    });
});
</script>
