document.addEventListener("DOMContentLoaded", () => {
    // all your code here

    let quill; // global

    document.addEventListener("DOMContentLoaded", function() {

        const fullToolbar = [
            [{
                header: [1, 2, 3, 4, 5, 6, false]
            }],
            ["bold", "italic", "underline", "strike"],
            [{
                color: []
            }, {
                background: []
            }],
            [{
                script: "sub"
            }, {
                script: "super"
            }],
            [{
                list: "ordered"
            }, {
                list: "bullet"
            }],
            [{
                indent: "-1"
            }, {
                indent: "+1"
            }],
            [{
                align: []
            }],
            ["blockquote", "code-block"],
            ["link", "image", "video"],
            ["clean"]
        ];

        // ✅ Initialize Quill ONCE
        quill = new Quill("#editor", {
            theme: "snow",
            modules: {
                toolbar: fullToolbar
            }
        });

        // ✅ Override image button to open media library
        const toolbar = quill.getModule("toolbar");
        toolbar.addHandler("image", function() {
            openZentraMediaLibraryModal('editor');
        });

        // Optional: sync content before submit
        window.syncQuillContent = function() {
            const html = quill.root.innerHTML;
            document.getElementById("event_description").value = html;
            return true;
        };
    });
    

    
    let selectedTags = [];
    const badgeContainer = document.getElementById('eventTagBadges');
    const tagSearchInput = document.getElementById('tagSearchInput');
    const tagSearchResults = document.getElementById('tagSearchResults');
    // --- SEARCH TAGS ---
    tagSearchInput.addEventListener('input', function() {
        const q = this.value.trim();
        if (!q) {
            tagSearchResults.innerHTML = '';
            return;
        }

        fetch(`/api/v1/tags/search.php?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(tags => renderTagSearch(tags, q));
    });

    function renderTagSearch(tags, query) {
        tagSearchResults.innerHTML = '';

        if (tags.length === 0) {
            tagSearchResults.innerHTML = `
                <button class="list-group-item list-group-item-action"
                        onclick="addTag('${query}', true)">
                    Create tag: <strong>${query}</strong>
                </button>`;
            return;
        }

        tags.forEach(tag => {
            tagSearchResults.innerHTML += `
                <button class="list-group-item list-group-item-action"
                        onclick="addTag('${tag.tag_name}', false, ${tag.tag_id})">
                    ${tag.tag_name}
                </button>`;
        });
    }
    // --- ADD TAG ---
    function addTag(name, isNew, tagId = null) {
        const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-');

        if (selectedTags.some(t => t.slug === slug)) return;

        // If it's a new tag, create it in DB first
        if (isNew) {
            fetch('/api/v1/tags/create.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name,
                        slug
                    })
                })
                .then(r => r.json())
                .then(data => {
                    selectedTags.push({
                        name,
                        slug,
                        tagId: data.tag_id,
                        isNew: false
                    });
                    renderBadges();
                });
        } else {
            // Existing tag
            selectedTags.push({
                name,
                slug,
                tagId,
                isNew: false
            });
            renderBadges();
        }
    }
    // --- RENDER BADGES ---
    function renderBadges() {
        badgeContainer.innerHTML = '';

        selectedTags.forEach((tag, index) => {
            badgeContainer.innerHTML += `
            <span class="badge bg-light d-inline-flex gap-1 align-items-center">
                ${tag.name}
                <span class="remove-tag text-muted" onclick="removeTag(${index})">&times;</span>
            </span>`;

        });

        document.getElementById('hiddenTags').value = JSON.stringify(selectedTags);
    }
    // --- REMOVE TAG ---
    function removeTag(index) {
        selectedTags.splice(index, 1);
        renderBadges();
    }
    // --- LOAD TAGS FOR EDIT MODE ---
    function loadEventTags(eventId) {
        fetch(`/api/v1/tags/event-tags.php?event_id=${eventId}`)
            .then(r => r.json())
            .then(tags => {
                selectedTags = tags.map(t => ({
                    name: t.tag_name,
                    slug: t.tag_slug,
                    tagId: t.tag_id,
                    isNew: false
                }));
                renderBadges();
            });
    }

    // Auto-run on page load (only if editing)
   document.addEventListener("DOMContentLoaded", () => {
    const eventIdEl = document.getElementById("event_id");

    // Only run when editing (event_id exists and is not empty)
    if (eventIdEl && eventIdEl.value.trim() !== "") {
        loadEventTags(eventIdEl.value);
    }
});


    
    (() => {
        // Prevent double initialization
        if (window.__posterUploaderInitialized) return;
        window.__posterUploaderInitialized = true;

        const dropzone = document.querySelector('.storage-dropzone');
        const fileInput = document.getElementById('fileInput-2');
        const preview = document.getElementById('posterPreview');
        const previewImg = document.getElementById('posterPreviewImg');
        const posterMediaIdInput = document.getElementById('poster_media_id');

        if (!dropzone || !fileInput) return;

        // CLICK → open file dialog
        //dropzone.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('click', () => {
            openZentraMediaLibraryModal("eventPoster"); // ✅ unified modal
        });

        window.applySelectedMedia = function(selected) {
            if (window.mediaLibraryMode === "eventPoster") {
                posterMediaIdInput.value = selected.id;
                previewImg.src = selected.url;
                preview.classList.remove('d-none');
                showPosterSuccess("Poster selected successfully");
            } else if (window.mediaLibraryMode === "editorInsert") {
                quill.insertEmbed(quill.getSelection().index, 'image', selected.url);
            }
            const modal = bootstrap.Modal.getInstance(document.getElementById('zentraMediaModal'));
            modal.hide();
        };
    })();
    

    
    function showPosterError(msg) {
        const box = document.getElementById('posterUploadError');
        box.innerHTML = `<span>${msg}</span>`;
        box.classList.remove('d-none');

        setTimeout(() => {
            box.classList.add('d-none');
        }, 5000);
    }

    function showPosterSuccess(msg) {
        const box = document.getElementById('posterUploadSuccess');
        box.innerHTML = `<span>${msg}</span>`;
        box.classList.remove('d-none');

        setTimeout(() => {
            box.classList.add('d-none');
        }, 5000);
    }
    
    
    function loadMediaLibrary(page = 1, search = "", filter = "all") {
        const grid = document.getElementById("mediaGrid");
        if (!grid) {
            console.error("mediaGrid not found");
            return;
        }

        grid.innerHTML = `<div class="text-center p-4">Loading...</div>`;

        const params = new URLSearchParams({
            page: page,
            q: search,
            filter: filter
        });

        fetch(`/api/v1/media/list.php?${params.toString()}`)
            .then(r => r.json())
            .then(files => {
                grid.innerHTML = "";

                if (!files.length) {
                    grid.innerHTML = `<div class="text-center text-muted p-4">No media found</div>`;
                    return;
                }

                files.forEach(file => {
                    grid.innerHTML += `
                    <div class="media-item" data-id="${file.id}" data-url="${file.url}" data-tags="${file.tags || ''}">
                    <img src="${file.url}" alt="${file.name}">
                    <div class="media-overlay">
                        <button class="tag-btn" title="Edit Tags"><i class="fa fa-tags"></i></button>
                        <button class="link-btn" title="Copy Link"><i class="fa fa-link"></i></button>
                    </div>
                    <input type="checkbox" class="select-checkbox">
                    </div>
                    `;
                });
            })
            .catch(err => {
                console.error("loadMediaLibrary error:", err);
                grid.innerHTML = `<div class="text-danger p-4">Error loading media</div>`;
            });
    }
    
    
    document.addEventListener('click', function(e) {
        const item = e.target.closest('.media-item');
        if (!item) return;

        if (e.target.closest('.tag-btn')) {
            console.log('Tag clicked for ID:', item.dataset.id);
            return;
        }

        if (e.target.closest('.link-btn')) {
            navigator.clipboard.writeText(item.dataset.url);
            console.log('Link copied:', item.dataset.url);
            return;
        }

        if (!e.target.closest('.media-overlay')) {
            const checkbox = item.querySelector('.select-checkbox');
            checkbox.checked = !checkbox.checked;
            item.classList.toggle('selected', checkbox.checked);
        }
    });

    function updateSelectedCount() {
        const count = document.querySelectorAll('.media-item.selected').length;
        const el = document.getElementById('selectedCount');
        if (el) el.textContent = `${count} item${count !== 1 ? 's' : ''} selected`;
    }

    document.addEventListener('click', function(e) {
        const img = e.target.closest('.media-item img');
        if (!img) return;

        const item = img.closest('.media-item');
        const checkbox = item.querySelector('.select-checkbox');
        if (!checkbox) return;

        // --- POSTER MODE: allow only one selection ---
        if (selectionMode === 'eventPoster') {
            // Unselect all other items
            document.querySelectorAll('.media-item.selected').forEach(i => {
                if (i !== item) {
                    i.classList.remove('selected');
                    i.querySelector('.select-checkbox').checked = false;
                }
            });
        }

        // Toggle this checkbox
        checkbox.click();
    });




    document.getElementById('insertSelectedMedia').addEventListener('click', function(e) {
        // Remove focus from the button BEFORE closing modal
        if (e && e.target) {
            e.target.blur();
        }
        // --- EDITOR MODE ---
        if (selectionMode === 'editor') {
            const selectedItems = document.querySelectorAll('.media-item.selected');

            selectedItems.forEach(item => {
                const url = item.dataset.url;
                const range = quill.getSelection(true);

                quill.insertEmbed(range.index, 'image', url);
                quill.setSelection(range.index + 1);
            });

            bootstrap.Modal.getInstance(document.getElementById('zentraMediaModal')).hide();
            return;
        }

        // --- POSTER MODE ---
        if (selectionMode === 'eventPoster') {
            const selected = document.querySelector('.media-item.selected');
            if (!selected) return;

            const url = selected.dataset.url;
            const id = selected.dataset.id;

            // Update preview
            document.getElementById('posterPreviewImg').src = url;
            document.getElementById('posterPreview').classList.remove('d-none');

            // Update hidden input
            document.getElementById('poster_media_id').value = id;

            // ⭐ NEW: Update event poster immediately
            const eventId = document.getElementById('event_id').value; // hidden input on edit page

            fetch('/ajax/update_event_poster.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        event_id: eventId,
                        library_id: id
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert("Failed to update event poster");
                    }
                });
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('zentraMediaModal')).hide();
            return;
        }
    });

    document.getElementById('zentraMediaModal')
        .addEventListener('hide.bs.modal', function() {
            // Remove focus from ANY element inside the modal before hiding
            if (document.activeElement && this.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        });
    });


document.addEventListener('DOMContentLoaded', function() {

    function updateEventURL() {
        const title = document.getElementById('event_title').value.trim();
        const startDT = document.getElementById('event_start_date_time').value.trim();

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
                        document.getElementById('event_url_hidden').value = res.url;
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

    document.getElementById('event_title').addEventListener('keyup', updateEventURL);
    document.getElementById('event_start_date_time').addEventListener('change', updateEventURL);

});


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

