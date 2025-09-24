        <!-- CSS Theme Quick Selection -->
        <div class="css-theme-section">
            <h3>🎨 Quick CSS Theme Selection</h3>
            <p>Change only the CSS theme without affecting templates:</p>
            <form method="post" style="display: inline-flex; align-items: center; gap: 10px;">
                <select name="css_theme">
                    <?php foreach ($available_css_themes as $css_theme): ?>
                        <option value="<?= htmlspecialchars($css_theme) ?>" <?= $css_theme === $current_css_theme ? "selected" : "" ?>>
                            <?= htmlspecialchars(ucwords(str_replace(["-", "_"], " ", $css_theme))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="set_css_theme" class="css-theme-btn">Apply CSS Theme</button>
            </form>
        </div>
