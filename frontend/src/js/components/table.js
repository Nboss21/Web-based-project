// src/js/components/table.js

/**
 * Generate a dynamic data table
 * @param {Object} options
 * @param {Array<{key: string, label: string, render: Function}>} options.columns - Table columns config
 * @param {Array<Object>} options.data - Data rows
 * @param {string} options.emptyMessage - Message when no data
 * @returns {string} HTML string of the table
 */
export function createTable({ columns, data = [], emptyMessage = 'No data available.' }) {
    if (data.length === 0) {
        return `
            <div style="text-align: center; padding: 3rem; color: var(--text-tertiary); background: var(--surface-color); border: 1px dashed var(--border-color); border-radius: var(--radius-md);">
                <p>${emptyMessage}</p>
            </div>
        `;
    }

    const headers = columns.map(col => `<th>${col.label}</th>`).join('');
    
    const rows = data.map(row => {
        const cells = columns.map(col => {
            const val = row[col.key];
            const content = col.render ? col.render(val, row) : val;
            return `<td>${content}</td>`;
        }).join('');
        return `<tr>${cells}</tr>`;
    }).join('');

    return `
        <div class="table-container card" style="padding: 0;">
            <table class="table">
                <thead>
                    <tr>${headers}</tr>
                </thead>
                <tbody>
                    ${rows}
                </tbody>
            </table>
        </div>
    `;
}
