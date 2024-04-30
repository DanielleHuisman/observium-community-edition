/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage js
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/**
 * Populates select elements with data based on a given entity type.
 * It uses the array key as a fallback for the value attribute if no explicit value field is provided.
 *
 * @param {string|null} device_id - The ID of the device or null if not applicable.
 * @param {string} target_id - The ID of the target select element to populate with entities.
 * @param {string} entity_type - The type of entity to fetch (e.g., sensor, port, device).
 */
async function getEntityList(device_id, target_id, entity_type) {
    const targetSelect = document.getElementById(target_id);
    targetSelect.innerHTML = ''; // Clear the select options

    try {
        // Construct the query based on whether device_id is provided
        const query = device_id ? `device_id=${encodeURIComponent(device_id)}&` : '';
        const response = await fetch(`ajax/get_entities.php?${query}entity_type=${encodeURIComponent(entity_type)}`);
        if (!response.ok) throw new Error('Network response was not ok.');

        const data = await response.json();
        populateSelect(targetSelect, data);
    } catch (error) {
        console.error('There has been a problem with your fetch operation:', error);
        // Optionally, handle errors, e.g., show a user-friendly message
    }

    // Refresh the select picker to update the UI
    $('.selectpicker').selectpicker('refresh');
}

/**
 * Converts the object data from the server into an array, sorts it by the 'name' field, and populates
 * the provided select element with options or optgroups based on the provided data.
 * If an option doesn't have a 'value' field, the key from the original data object is used as its value.
 *
 * @param {HTMLElement} selectElement - The select element to populate.
 * @param {Object} data - The data used to populate the select element, in key-value pairs.
 */
function populateSelect(selectElement, data) {
    // Convert object to array of entries, sort by 'name', and map to a new structure
    const dataArray = Object.entries(data).sort(([keyA, a], [keyB, b]) => {
        // Case-insensitive comparison of names
        const nameA = a.name.toLowerCase();
        const nameB = b.name.toLowerCase();
        return nameA.localeCompare(nameB);
    }).map(([key, item]) => {
        // Use key as the value if 'value' field is not present
        return { ...item, value: item.value || key };
    });

    // Group data by the 'group' field
    const groupedData = dataArray.reduce((acc, item) => {
        const group = item.group || 'Other'; // Default group name if not specified
        acc[group] = acc[group] || [];
        acc[group].push(item);
        return acc;
    }, {});

    // Generate HTML for each group and its options
    const optionsHtml = Object.keys(groupedData).map(group => {
        const groupOptions = groupedData[group].map(item => createOption(item)).join('');
        return `<optgroup label="${group}">${groupOptions}</optgroup>`;
    }).join('');

    selectElement.innerHTML = optionsHtml;
}

/**
 * Creates an HTML string for an option element based on the provided item data.
 *
 * @param {Object} item - The item data containing information for the option element.
 * @returns {string} - The HTML string for the option element.
 */
function createOption(item) {
    // Prepare additional attributes if they exist
    const subtextAttr = item.subtext ? `data-subtext="${item.subtext}"` : '';
    const iconAttr = item.icon ? `data-icon="${item.icon}"` : '';
    const classAttr = item.class ? `class="${item.class}"` : '';
    const contentAttr = item.content ? `data-content="${item.content}"` : '';

    // Return the HTML string for the option element
    return `<option value="${item.value}" ${subtextAttr} ${iconAttr} ${classAttr} ${contentAttr}>${item.name}</option>`;
}

// Example usage:
// getEntityList('123', 'select-target', 'sensor'); // For a specific device
// getEntityList(null, 'select-target', 'device');  // For a device list

function loadDevicesIntoSelects() {
    document.querySelectorAll('select[data-load="devices"]').forEach(selectElement => {

        console.log('Loading devices into :');
        console.log(selectElement);

        getEntityList(null, selectElement.id, 'device');
    });
}

document.addEventListener('DOMContentLoaded', loadDevicesIntoSelects);

// Function to make an element draggable
function makeDraggable(element) {
    var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
    var draghandle = element.querySelector('.drag-handle');

    draghandle.onmousedown = dragMouseDown;

    function dragMouseDown(e) {
        e = e || window.event;
        e.preventDefault();
        pos3 = e.clientX;
        pos4 = e.clientY;
        document.onmouseup = closeDragElement;
        document.onmousemove = elementDrag;
    }

    function elementDrag(e) {
        e = e || window.event;
        e.preventDefault();
        pos1 = pos3 - e.clientX;
        pos2 = pos4 - e.clientY;
        pos3 = e.clientX;
        pos4 = e.clientY;
        element.style.top = (element.offsetTop - pos2) + "px";
        element.style.left = (element.offsetLeft - pos1) + "px";
    }

    function closeDragElement() {
        document.onmouseup = null;
        document.onmousemove = null;
    }
}