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
</script>

<script>
function updateEventURL() {
    const title = document.getElementById('event_title').value.trim();
    const startDT = document.getElementById('event_start_date_time').value.trim();

    // Do nothing if both fields are empty
    if (title === "" && startDT === "") {
        return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "ajax/generate_event_url.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onload = function() {
        if (this.status === 200) {
            try {
                const res = JSON.parse(this.responseText);
                if (res.success && res.url) {
                    document.getElementById('event-url').innerText = res.url;
                }
            } catch (e) {
                console.error("Invalid JSON from server:", this.responseText);
            }
        }
    };

    xhr.send(
        "title=" + encodeURIComponent(title) +
        "&start_dt=" + encodeURIComponent(startDT)
    );
}

// Fire on typing + date/time change
document.getElementById('event_title').addEventListener('keyup', updateEventURL);
document.getElementById('event_start_date_time').addEventListener('change', updateEventURL);
document.getElementById('event_url_hidden').value = res.url;
</script>

<script>
// Validate start/end datetime
function validateEventTimes() {
    const start = document.getElementById('event_start_date_time').value;
    const end = document.getElementById('event_end_date_time').value;

    if (!start || !end) return true;

    const startDT = new Date(start);
    const endDT = new Date(end);

    if (endDT <= startDT) {
        alert("Event End Date & Time must be AFTER Event Start Date & Time.");
        document.getElementById('event_end_date_time').value = "";
        return false;
    }

    return true;
}

document.getElementById('event_end_date_time')
    .addEventListener('change', validateEventTimes);

document.getElementById('event_start_date_time')
    .addEventListener('change', validateEventTimes);


// All‑day event handler
function setAllDayEvent(isAllDay) {
    const startInput = document.getElementById('event_start_date_time');
    const endInput = document.getElementById('event_end_date_time');

    if (!startInput.value) {
        alert("Please select a start date first.");
        return;
    }

    const date = startInput.value.split("T")[0];

    if (isAllDay) {
        startInput.value = date + "T00:00";
        endInput.value = date + "T23:59";
    }

    validateEventTimes();
}
</script>
