import './bootstrap';

// ---------------------------------------------------------------------------
// Font Awesome
// ---------------------------------------------------------------------------
// Imported here (rather than a <link> to cdnjs) so the icon font ships in the
// Vite bundle with its webfont files hashed and served from our own origin.
// The public site, login and certificate pages previously hard-depended on the
// cdnjs CDN, so any CDN outage stripped every icon from the UI.
import '@fortawesome/fontawesome-free/css/all.min.css';

// ---------------------------------------------------------------------------
// Alpine.js
// ---------------------------------------------------------------------------
// Loaded from the bundle instead of cdn.jsdelivr.net. Both layouts (admin and
// frontend) plus 15 views rely on x-data/x-show/x-transition directives.
//
// Module scripts are deferred, so this executes after the document has been
// parsed — Alpine.start() can therefore walk the DOM immediately. No view
// registers custom Alpine.data()/directive() components, so starting here is
// safe and there is no ordering hazard with page-level inline scripts.
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
