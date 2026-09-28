// ---------------------------------------------------------------------------
// Admin-only vendor bundle
// ---------------------------------------------------------------------------
// Chart.js, SortableJS and Fabric.js are each used on a handful of authenticated
// dashboard pages (see the table below). They used to be pulled from
// cdn.jsdelivr.net / cdnjs.cloudflare.com on demand, which made those pages
// fail without an internet connection and leaked visitor IPs to third parties.
//
// They are bundled here — a separate entry from app.js — so the public site
// never downloads ~500 KB of charting/annotation code it will not use.
//
//   Chart.js   dashboard/index, dashboard/accounts/{index,reports},
//              dashboard/salaries/report, student/results
//   SortableJS dashboard/courses/videos/index, dashboard/materials/index
//   Fabric.js  dashboard/exams/review-single, dashboard/exams/review-submissions
//
// Each library is exposed on `window` because the calling views were written
// against the CDN globals (`new Chart(...)`, `Sortable.create(...)`,
// `new fabric.Canvas(...)`). Keeping the same global names means the view code
// needed no rewrite beyond the Fabric v5 -> v7 API migration.
import Chart from 'chart.js/auto';
import Sortable from 'sortablejs';
import * as fabric from 'fabric';

window.Chart = Chart;
window.Sortable = Sortable;
window.fabric = fabric;
