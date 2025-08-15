<?php
header('Content-Type: text/css');
?>
/* tour.css */

.shepherd-custom {
    max-width: 400px;
}

.shepherd-element.shepherd-custom {
    background-color: #f8f9fb;
    border-radius: 0.5rem;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
}

.shepherd-custom .shepherd-header {
    background-color: #254c90;
    padding: 1rem;
}

.shepherd-custom .shepherd-title {
    color: #ffffff;
    font-weight: 600;
    font-size: 1.125rem;
}

.shepherd-custom .shepherd-cancel-icon {
    color: #ffffff;
    opacity: 0.8;
}

.shepherd-custom .shepherd-text {
    color: #1d3870;
    padding: 1rem;
    font-size: 0.95rem;
    line-height: 1.6;
}

.shepherd-custom .shepherd-button {
    background-color: #254c90;
    color: #ffffff;
    border-radius: 0.375rem;
    padding: 0.5rem 1rem;
    font-weight: 500;
    transition: background-color 0.2s;
}

.shepherd-custom .shepherd-button:not(:disabled):hover {
    background-color: #1d3870;
}

.shepherd-custom .shepherd-button.shepherd-button-secondary {
    background-color: #e5e7eb;
    color: #374151;
}

.shepherd-custom .shepherd-button.shepherd-button-secondary:not(:disabled):hover {
    background-color: #d1d5db;
}