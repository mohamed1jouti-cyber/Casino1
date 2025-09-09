/**
 * Game Action History Tracker
 * 
 * This script provides functionality to track detailed player actions during gameplay
 * and store them in localStorage for later viewing.
 */

// Initialize action history if not exists
if (!localStorage.getItem("action_history")) {
  localStorage.setItem("action_history", JSON.stringify([]));
}

/**
 * Add a player action to history
 * @param {string} game - Game name (blackjack, roulette, slots, etc.)
 * @param {string} action - Action performed (hit, stand, bet, spin, etc.)
 * @param {Object} details - Additional details about the action
 */
function addPlayerAction(game, action, details = {}) {
  // Always record locally for per-user history
  
  let actionHistory = JSON.parse(localStorage.getItem("action_history") || "[]");
  
  const newAction = {
    id: actionHistory.length + 1,
    game: game,
    action: action,
    details: details,
    timestamp: new Date().toISOString(),
    username: localStorage.getItem("username") || "Player"
  };
  
  // Limit history to 100 most recent actions to prevent localStorage overflow
  actionHistory.unshift(newAction); // Add to beginning for chronological display
  if (actionHistory.length > 100) {
    actionHistory = actionHistory.slice(0, 100);
  }
  
  localStorage.setItem("action_history", JSON.stringify(actionHistory));
}

/**
 * Get action history, optionally filtered by game
 * @param {string} game - Optional game name to filter by
 * @returns {Array} Array of action history objects
 */
function getActionHistory(game = null) {
  const history = JSON.parse(localStorage.getItem("action_history") || "[]");
  const currentUser = (localStorage.getItem("username") || "Player").toString();
  const filtered = history.filter(item => String(item.username).toLowerCase() === currentUser.toLowerCase());
  if (game) {
    return filtered.filter(item => item.game === game);
  }
  return filtered;
}

/**
 * Clear action history
 */
function clearActionHistory() {
  localStorage.setItem("action_history", JSON.stringify([]));
}

/**
 * Format timestamp for display
 * @param {string} timestamp - ISO timestamp string
 * @returns {string} Formatted date/time string
 */
function formatTimestamp(timestamp) {
  const date = new Date(timestamp);
  return date.toLocaleString();
}