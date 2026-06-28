                                               <?php
/**
 * PHP Internal Task Scheduler
 * Simuliert Cronjobs ohne externe Abhängigkeiten
 */

class PHPScheduler {
    
    private $tasksFile = 'tasks.json';
    private $lockFile = 'scheduler.lock';
    private $logFile = 'scheduler.log';
    
    /**
     * Registriert einen neuen Task
     */
    public function addTask($name, $callback, $interval, $params = []) {
        $tasks = $this->loadTasks();
        
        $tasks[$name] = [
            'callback' => $callback,
            'interval' => $interval, // in Sekunden
            'params' => $params,
            'last_run' => 0,
            'next_run' => time(),
            'enabled' => true
        ];
        
        $this->saveTasks($tasks);
        return true;
    }
    
    /**
     * Entfernt einen Task
     */
    public function removeTask($name) {
        $tasks = $this->loadTasks();
        unset($tasks[$name]);
        $this->saveTasks($tasks);
    }
    
    /**
     * Führt fällige Tasks aus
     */
    public function run() {
        // Verhindere mehrfache gleichzeitige Ausführungen
        if (!$this->acquireLock()) {
            $this->log("Scheduler läuft bereits");
            return;
        }
        
        try {
            $tasks = $this->loadTasks();
            $now = time();
            
            foreach ($tasks as $name => $task) {
                if (!$task['enabled']) {
                    continue;
                }
                
                // Prüfe ob Task fällig ist
                if ($now >= $task['next_run']) {
                    $this->log("Führe Task aus: {$name}");
                    
                    try {
                        // Callback ausführen
                        if (is_callable($task['callback'])) {
                            call_user_func_array($task['callback'], $task['params']);
                        } elseif (file_exists($task['callback'])) {
                            // Falls es ein PHP-Script ist
                            include $task['callback'];
                        }
                        
                        // Nächste Ausführung berechnen
                        $tasks[$name]['last_run'] = $now;
                        $tasks[$name]['next_run'] = $now + $task['interval'];
                        
                        $this->log("Task erfolgreich: {$name}");
                        
                    } catch (Exception $e) {
                        $this->log("Fehler in Task {$name}: " . $e->getMessage());
                    }
                }
            }
            
            $this->saveTasks($tasks);
            
        } finally {
            $this->releaseLock();
        }
    }
    
    /**
     * Listet alle Tasks auf
     */
    public function listTasks() {
        return $this->loadTasks();
    }
    
    /**
     * Aktiviert/Deaktiviert einen Task
     */
    public function toggleTask($name, $enabled = true) {
        $tasks = $this->loadTasks();
        if (isset($tasks[$name])) {
            $tasks[$name]['enabled'] = $enabled;
            $this->saveTasks($tasks);
            return true;
        }
        return false;
    }
    
    /**
     * Hilfs-Methoden für Intervalle
     */
    public static function everyMinute() { return 60; }
    public static function everyMinutes($n) { return 60 * $n; }
    public static function everyHour() { return 3600; }
    public static function everyHours($n) { return 3600 * $n; }
    public static function everyDay() { return 86400; }
    public static function everyDays($n) { return 86400 * $n; }
    public static function everyWeek() { return 604800; }
    
    // Interne Methoden
    
    private function loadTasks() {
        if (!file_exists($this->tasksFile)) {
            return [];
        }
        
        $content = file_get_contents($this->tasksFile);
        return json_decode($content, true) ?: [];
    }
    
    private function saveTasks($tasks) {
        file_put_contents($this->tasksFile, json_encode($tasks, JSON_PRETTY_PRINT));
    }
    
    private function acquireLock() {
        if (file_exists($this->lockFile)) {
            $lockTime = filemtime($this->lockFile);
            // Wenn Lock älter als 5 Minuten, als "stale" betrachten
            if (time() - $lockTime > 300) {
                unlink($this->lockFile);
            } else {
                return false;
            }
        }
        
        file_put_contents($this->lockFile, getmypid());
        return true;
    }
    
    private function releaseLock() {
        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
}

// ===== VERWENDUNG =====

// Tasks definieren
$scheduler = new PHPScheduler();

// Task 1: Funktion alle 5 Minuten
$scheduler->addTask('cleanup', function() {
    echo "Cleanup läuft...\n";
    // Deine Cleanup-Logik hier
}, PHPScheduler::everyMinutes(5));

// Task 2: Script jede Stunde
$scheduler->addTask('backup', 'backup.php', PHPScheduler::everyHour());

// Task 3: Funktion mit Parametern alle 30 Minuten
$scheduler->addTask('notify', function($email, $message) {
    echo "Sende Email an {$email}: {$message}\n";
    // mail($email, 'Notification', $message);
}, PHPScheduler::everyMinutes(30), ['admin@example.com', 'Status Update']);

// Task 4: Täglicher Report
$scheduler->addTask('daily_report', function() {
    echo "Erstelle täglichen Report...\n";
    // Report-Logik
}, PHPScheduler::everyDay());

// Tasks auflisten
echo "=== Geplante Tasks ===\n";
foreach ($scheduler->listTasks() as $name => $task) {
    echo "Task: {$name}\n";
    echo "  Intervall: {$task['interval']} Sekunden\n";
    echo "  Letzte Ausführung: " . ($task['last_run'] ? date('Y-m-d H:i:s', $task['last_run']) : 'Nie') . "\n";
    echo "  Nächste Ausführung: " . date('Y-m-d H:i:s', $task['next_run']) . "\n";
    echo "  Status: " . ($task['enabled'] ? 'Aktiv' : 'Deaktiviert') . "\n\n";
}

// Task deaktivieren
// $scheduler->toggleTask('cleanup', false);

// Task entfernen
// $scheduler->removeTask('cleanup');

// Scheduler ausführen
$scheduler->run();

?>
