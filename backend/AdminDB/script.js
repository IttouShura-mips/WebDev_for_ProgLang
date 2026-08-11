// ============================================
// ICF Admin Panel - Shared Scripts
// ============================================

document.addEventListener('DOMContentLoaded', () => {
  // Header live search
  const searchInput = document.getElementById('userSearchInput') || document.getElementById('mailSearchInput');
  const searchResults = document.getElementById('searchResults');

  if (searchInput && searchResults) {
    searchInput.addEventListener('input', (e) => {
      const query = e.target.value.trim().toLowerCase();
      if (query.length < 1) {
        searchResults.classList.remove('active');
        return;
      }

      // Search across visible tables on the page
      const tables = document.querySelectorAll('table tbody tr');
      const matches = [];

      tables.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(query)) {
          const nameCell = row.querySelector('td:nth-child(2)') || row.querySelector('td:first-child');
          const roleCell = row.querySelector('td:nth-child(3)') || row.querySelector('td:nth-child(2)');
          if (nameCell) {
            matches.push({
              name: nameCell.textContent.trim(),
              role: roleCell ? roleCell.textContent.trim() : '',
              element: row
            });
          }
        }
      });

      if (matches.length > 0) {
        searchResults.innerHTML = matches.slice(0, 5).map(m => `
          <div class="search-result-item" onclick="scrollToRow(this)" data-target="${m.name}">
            <div class="search-result-info">
              <span class="search-result-name">${m.name}</span>
              <span class="search-result-role">${m.role}</span>
            </div>
            <i class="fa-solid fa-arrow-right" style="color:var(--primary-neon);font-size:0.75rem;"></i>
          </div>
        `).join('');
        searchResults.classList.add('active');
      } else {
        searchResults.innerHTML = '<div class="no-results">No results found</div>';
        searchResults.classList.add('active');
      }
    });

    // Close search when clicking outside
    document.addEventListener('click', (e) => {
      if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.classList.remove('active');
      }
    });
  }
});

function scrollToRow(el) {
  const targetName = el.getAttribute('data-target');
  const rows = document.querySelectorAll('table tbody tr');
  rows.forEach(row => {
    const nameCell = row.querySelector('td:nth-child(2)') || row.querySelector('td:first-child');
    if (nameCell && nameCell.textContent.trim() === targetName) {
      row.scrollIntoView({ behavior: 'smooth', block: 'center' });
      row.style.backgroundColor = 'rgba(13, 245, 227, 0.15)';
      setTimeout(() => {
        row.style.backgroundColor = '';
      }, 2000);
    }
  });
  document.getElementById('searchResults').classList.remove('active');
}