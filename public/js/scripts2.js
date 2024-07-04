document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('toggle-sidebar').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('closed');
    });

    const databaseHeaders = document.querySelectorAll('.database-header');
    databaseHeaders.forEach(header => {
        header.addEventListener('click', function() {
            // Close other databases
            databaseHeaders.forEach(otherHeader => {
                if (otherHeader !== header) {
                    const otherContent = otherHeader.nextElementSibling;
                    otherContent.style.display = 'none';
                    otherHeader.classList.remove('expanded');
                    otherHeader.classList.add('collapsed');
                }
            });

            toggleCollapse(header);
            selectDatabase(header.dataset.database);
        });
    });

    document.querySelectorAll('.table-header').forEach(header => {
        header.addEventListener('click', function() {
            const tableName = header.dataset.table;
            const databaseName = header.closest('.database-content').previousElementSibling.dataset.database;

            // Close other tables
            document.querySelectorAll('.table-header').forEach(otherHeader => {
                if (otherHeader !== header) {
                    const otherContent = otherHeader.nextElementSibling;
                    otherContent.style.display = 'none';
                    otherHeader.classList.remove('expanded');
                    otherHeader.classList.add('collapsed');
                }
            });

            toggleCollapse(header);
            selectTable(databaseName, tableName, header.nextElementSibling);
        });
    });

    if (document.querySelector('.output tbody').children.length === 0) {
        // Display empty table initially
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = `<td colspan="100%">No data available</td>`;
        document.getElementById('table-body').appendChild(emptyRow);
    }

    document.getElementById('query-form').addEventListener('submit', function(event) {
        event.preventDefault();
        executeQuery();
    });
});

function toggleCollapse(element) {
    const content = element.nextElementSibling;
    if (content.style.display === 'block') {
        content.style.display = 'none';
        element.classList.remove('expanded');
        element.classList.add('collapsed');
    } else {
        content.style.display = 'block';
        element.classList.remove('collapsed');
        element.classList.add('expanded');
    }
}

function selectDatabase(database) {
    fetch(`/select-database/${database}`)
        .then(response => response.json())
        .then(data => {
            // Populate tables
            const databaseContent = document.querySelector(`.database-header[data-database="${database}"]`).nextElementSibling;
            databaseContent.innerHTML = '';

            data.tables.forEach(table => {
                const tableElement = document.createElement('div');
                tableElement.className = 'table-header list-group-item collapsed';
                tableElement.dataset.table = table;
                tableElement.innerHTML = `<i class="fas fa-table" style="color: orange;"></i><span>${table}</span>`;
                tableElement.addEventListener('click', function() {
                    // Close other tables
                    document.querySelectorAll('.table-header').forEach(otherHeader => {
                        if (otherHeader !== tableElement) {
                            const otherContent = otherHeader.nextElementSibling;
                            otherContent.style.display = 'none';
                            otherHeader.classList.remove('expanded');
                            otherHeader.classList.add('collapsed');
                        }
                    });

                    toggleCollapse(tableElement);
                    selectTable(database, table, tableElement.nextElementSibling);
                });

                const tableContent = document.createElement('div');
                tableContent.className = 'table-content column-list';
                tableContent.style.display = 'none';

                databaseContent.appendChild(tableElement);
                databaseContent.appendChild(tableContent);
            });

            databaseContent.style.display = 'block';
            const databaseHeader = document.querySelector(`.database-header[data-database="${database}"]`);
            databaseHeader.classList.remove('collapsed');
            databaseHeader.classList.add('expanded');

            // Display selected database message
            const databaseMessage = document.getElementById('database-message');
            databaseMessage.textContent = `Selected Database: ${database}`;
            databaseMessage.style.display = 'block';

            // Update selected database span
            document.getElementById('selected-database').textContent = database;
        })
        .catch(error => console.error('Error:', error));
}

function selectTable(database, table, tableContent) {
    fetch(`/select-table/${database}/${table}`)
        .then(response => response.json())
        .then(data => {
            // Populate columns
            tableContent.innerHTML = '';
            data.columns.forEach(column => {
                const columnElement = document.createElement('div');
                columnElement.className = 'column-header list-group-item';

                if (column.Key === 'PRI') {
                    columnElement.classList.add('primary-key');
                    columnElement.innerHTML = `<i class="fas fa-key"></i><span>${column.Field}</span> <span class="text-muted">(${column.Type})</span>`;
                } else if (column.Key === 'MUL') {
                    columnElement.classList.add('foreign-key');
                    columnElement.innerHTML = `<i class="fas fa-key"></i><span>${column.Field}</span> <span class="text-muted">(${column.Type})</span>`;
                } else {
                    columnElement.classList.add('normal');
                    columnElement.innerHTML = `<i class="fas fa-columns"></i><span>${column.Field}</span> <span class="text-muted">(${column.Type})</span>`;
                }

                tableContent.appendChild(columnElement);
            });

            tableContent.style.display = 'block';
        })
        .catch(error => console.error('Error:', error));
}

function executeQuery() {
    const queryForm = document.getElementById('query-form');
    const queryResult = document.getElementById('query-result');
    const selectedDatabase = document.getElementById('selected-database').textContent;

    fetch(queryForm.action, {
        method: queryForm.method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: JSON.stringify({
            query: document.getElementById('editor').value
        })
    })
    .then(response => response.json())
    .then(data => {
        const tableBody = queryResult.querySelector('tbody');
        tableBody.innerHTML = '';

        if (data.result.length > 0) {
            const headerRow = document.createElement('tr');
            Object.keys(data.result[0]).forEach(key => {
                const headerCell = document.createElement('th');
                headerCell.textContent = key;
                headerRow.appendChild(headerCell);
            });
            queryResult.querySelector('thead').innerHTML = '';
            queryResult.querySelector('thead').appendChild(headerRow);

            data.result.forEach(row => {
                const dataRow = document.createElement('tr');
                Object.values(row).forEach(value => {
                    const dataCell = document.createElement('td');
                    dataCell.textContent = value;
                    dataRow.appendChild(dataCell);
                });
                tableBody.appendChild(dataRow);
            });
        } else {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = '<td colspan="100%">No data available</td>';
            tableBody.appendChild(emptyRow);
        }

        document.getElementById('editor').value = data.query;

        // Show success message if any
        const databaseMessage = document.getElementById('database-message');
        databaseMessage.textContent = data.message || 'Query executed successfully';
        databaseMessage.style.display = 'block';

        // If a new table was created, refresh the table list
        if (data.query.trim().toLowerCase().startsWith('create table')) {
            selectDatabase(selectedDatabase);
        }
    })
    .catch(error => console.error('Error:', error));
}
