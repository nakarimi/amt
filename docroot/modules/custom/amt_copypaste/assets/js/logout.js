/**
 * This runs on user/login page and empties the localStorage
 * To make sure that no event is in local storage which was
 * meant to be copied or moved.
 * 
 * So when the new user or the previous user logs in again, the
 * moving item is deleted from localStorage and the container
 * box doesn't show anymore.
 */
localStorage.removeItem("opEvent");