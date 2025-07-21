import axios from 'axios';

/**
 * Calculate the total volume of packages
 * @param {Array} packages - Array of package objects
 * @returns {number} Total volume in cubic meters
 */
export function calculateTotalVolume(packages = []) {
  if (!Array.isArray(packages)) return 0;
  
  const total = packages.reduce((sum, pkg) => {
    const volume = parseFloat(pkg.volume) || 0;
    return sum + volume;
  }, 0);
  
  return parseFloat(total.toFixed(2));
}

/**
 * Calculate the total weight of packages
 * @param {Array} packages - Array of package objects
 * @returns {number} Total weight in kilograms
 */
export function calculateTotalWeight(packages = []) {
  if (!Array.isArray(packages)) return 0;

  const total = packages.reduce((sum, pkg) => {
    const weight = parseFloat(pkg.weight) || 0;
    return sum + weight;
  }, 0);

  return parseFloat(total.toFixed(2));
}

/**
 * Get initials from a name
 * @param {string} name - Full name
 * @returns {string} Initials
 */
export function getInitials(name) {
  if (!name) return 'NA';
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
}

/**
 * Format a date string (date only)
 * @param {string} dateString - Date string to format
 * @returns {string} Formatted date
 */
export function formatDate(dateString) {
  if (!dateString) return 'N/A';
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString();
  } catch (e) {
    return 'N/A';
  }
}

/**
 * Format a date and time string
 * @param {string} dateString - Date string to format
 * @returns {string} Formatted date and time
 */
export function formatDateTime(dateString) {
  if (!dateString) return 'N/A';
  try {
    const date = new Date(dateString);
    return date.toLocaleString();
  } catch (e) {
    return 'N/A';
  }
}

/**
 * Calculate ETA based on departure time and region durations
 * @param {string|Date} departureTime - Departure time (ISO string or Date)
 * @param {number} fromRegionId
 * @param {number} toRegionId
 * @param {Array} durations - Array of {from_region_id, to_region_id, estimated_minutes}
 * @returns {Date} ETA as a Date object
 */
export function calculateETA(departureTime, fromRegionId, toRegionId, durations) {
  const duration = durations.find(d =>
    d.from_region_id === fromRegionId && d.to_region_id === toRegionId
  )?.estimated_minutes || 1440; // default 24 hours

  const eta = new Date(departureTime);
  eta.setMinutes(eta.getMinutes() + duration);
  return eta;
}

/**
 * Fetch region-to-region durations from API
 * @returns {Promise<Array>} Resolves to array of durations
 */
export function getRegionDurations() {
  return axios.get('/api/region-durations').then(res => res.data);
}


