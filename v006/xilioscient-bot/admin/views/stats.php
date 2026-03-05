<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1><?php _e('Statistiche Bot', 'xilioscient-bot'); ?></h1>

    <div class="welcome-panel">
        <div class="welcome-panel-column-container">
            <div class="welcome-panel-column">
                <h3><?php _e('Conversazioni', 'xilioscient-bot'); ?></h3>
                <ul>
                    <li>Totale: <span id="xsbot-total-conversations">0</span></li>
                    <li>Oggi: <span id="xsbot-today-conversations">0</span></li>
                    <li>Settimana: <span id="xsbot-week-conversations">0</span></li>
                </ul>
            </div>
            <div class="welcome-panel-column">
                <h3><?php _e('Feedback & Sessioni', 'xilioscient-bot'); ?></h3>
                <ul>
                    <li>Sessioni Uniche: <span id="xsbot-unique-sessions">0</span></li>
                    <li>Positivi: <span id="xsbot-positive-feedback">0</span></li>
                    <li>Negativi: <span id="xsbot-negative-feedback">0</span></li>
                    <li>% Positiva: <span id="xsbot-feedback-percentage">0%</span></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3><?php _e('Andamento Giornaliero', 'xilioscient-bot'); ?></h3>
        <canvas id="xsbot-daily-chart" width="400" height="150"></canvas>
    </div>
</div>