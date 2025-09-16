<style>
/* Ensure chart widgets use full height */
.fi-wi-chart .fi-section-content-ctn {
    height: auto;
}

/* Target specific chart widgets for full height */
[wire\\:id*="AlertsTimelineChart"] .fi-section-content,
[wire\\:id*="TankStatusDistribution"] .fi-section-content {
    min-height: 400px;
    height: 400px;
}

[wire\\:id*="AlertsTimelineChart"] .fi-section-content > div,
[wire\\:id*="TankStatusDistribution"] .fi-section-content > div {
    height: 100% !important;
    min-height: 380px;
}

[wire\\:id*="AlertsTimelineChart"] canvas,
[wire\\:id*="TankStatusDistribution"] canvas {
    height: 300px !important;
    min-height: 300px !important;
    max-height: 300px !important;
}

/* Ensure proper spacing for legends */
[wire\\:id*="AlertsTimelineChart"] .fi-section-content,
[wire\\:id*="TankStatusDistribution"] .fi-section-content {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
</style>