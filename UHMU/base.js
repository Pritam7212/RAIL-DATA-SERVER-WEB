function getDeviceType() {
  const userAgent = navigator.userAgent.toLowerCase();
  const isTablet = /ipad|tablet|playbook|silk/.test(userAgent);
  const isMobile =
    /mobile|iphone|ipod|android|blackberry|phone/.test(userAgent) && !isTablet;

  if (isMobile) return "mobile";
  if (isTablet) return "tablet";
  return "desktop";
}

function isDatePastThreshold(dateStr, thresholdInMillis) {
  const parsedDate = new Date(dateStr);
  return new Date() - parsedDate > thresholdInMillis;
}

function getLargestNDates(dateStrings, n) {
  // Convert date strings to Date objects
  const dateObjects = dateStrings.map((dateStr) => {
    const [date, time] = dateStr.split(" ");
    const [day, month, year] = date.split("-").map(Number);
    const [hours, minutes, seconds] = time.split(":").map(Number);
    return new Date(day, month - 1, year, hours, minutes, seconds);
  });
  // Sort the date objects in descending order
  dateObjects.sort((a, b) => b - a);

  // Get the largest n dates and convert back to the original format
  return dateObjects.slice(0, n).map((dateObj) => {
    const pad = (num) => String(num).padStart(2, "0");
    const day = pad(dateObj.getDate());
    const month = pad(dateObj.getMonth() + 1);
    const year = dateObj.getFullYear();
    const hours = pad(dateObj.getHours());
    const minutes = pad(dateObj.getMinutes());
    const seconds = pad(dateObj.getSeconds());
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
  });
}

async function getData(query) {
  try {
    const response = await fetch(`../../../../getData.php?${query}`);
    return response.ok ? response.json() : [];
  } catch (error) {
    console.error("Error fetching data:", error);
    return [];
  }
}

async function getLocations() {
  try {
    const response = await fetch(`../../../getLocations.php`);
    return response.ok ? response.json() : [];
  } catch (error) {
    console.error("Error fetching data:", error);
    return [];
  }
}

async function getConfig() {
  try {
    const response = await fetch("../../pointconfig.json");
    return response.json();
  } catch (error) {
    console.error("Error loading the JSON file:", error);
  }
}

async function getActions(key) {
  try {
    const response = await fetch("../../error_actions.json");
    if (!response.ok) {
      throw new Error("Network response was not ok");
    }
    actions = await response.json();
  } catch (error) {
    console.error("Error fetching data:", error);
  }
  return actions[key];
}

async function getCause(){
  try {
      const response = await fetch('../../error_codes.json');
      if (!response.ok) {
          throw new Error('Network response was not ok');
      }
      causesJson = await response.json();
      //console.log(cause[key])
  } catch (error) {
      console.error('Error fetching data:', error);
  }
  return causesJson;
}

// Function to get a specific parameter's value from the URL
function getQueryParam(param) {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get(param);
}

function updateCircleColors(arr, cls) {
  const circles = document.querySelectorAll(cls); // Get all circles
  for (let i = 0; i < arr.length && i < circles.length; i++) {
    if (arr[i] === 1) {
      circles[i].style.backgroundColor = "var(--primary-green)"; // Set circle to green
    } else if (arr[i] === 0) {
      circles[i].style.backgroundColor = "var(--primary-red)"; // Set circle to red
    }
  }
}

function hideNTypeClientData(clientType) {
  if (clientType === "S") {
    const switchContainer = document.querySelector(".switch-container");
    if (switchContainer) {
      switchContainer.style.display = "none";
    }
  } else if (clientType === "U") {
    const containerTable = document.querySelector(".container-table");
    if (containerTable) {
      containerTable.style.display = "none";
    }
  }
}

function getAllUrlParameters() {
  const params = new URLSearchParams(window.location.search);
  const entries = [...params.entries()]; // Convert to array for easy manipulation

  const result = {};
  entries.forEach(([key, value]) => {
    result[key] = value;
  });

  return result;
}

function combineParametersToQueryString(parameters) {
  const params = new URLSearchParams(parameters);
  return params.toString(); // Returns a URL query string
}

function moveElementBeforePrevious(element) {
  const previousSibling = element.previousElementSibling;

  if (previousSibling) {
    previousSibling.parentNode.insertBefore(element, previousSibling);
  } else {
    console.log("The element has no previous sibling to move before.");
  }
}

function checkOrientation() {
  if (
    window.matchMedia("(orientation: portrait)").matches &&
    window.location.href.indexOf("desktop") != -1
  ) {
    if (combineParametersToQueryString(getAllUrlParameters()) != "") {
      window.location.href =
        "../mobile/index.html?" +
        combineParametersToQueryString(getAllUrlParameters()); // Redirect for portrait
    } else {
      window.location.href = "../mobile/index.html"; // Redirect for portrait
    }
  } else if (
    window.matchMedia("(orientation: landscape)").matches &&
    window.location.href.indexOf("mobile") != -1
  ) {
    if (combineParametersToQueryString(getAllUrlParameters()) != "") {
      window.location.href =
        "../desktop/index.html?" +
        combineParametersToQueryString(getAllUrlParameters()); // Redirect for portrait
    } else {
      window.location.href = "../desktop/index.html"; // Redirect for portrait
    }
  }
}

window.addEventListener("resize", checkOrientation);
window.addEventListener("OrientationChange", checkOrientation);
checkOrientation();
console.log(window.matchMedia("(orientation: landscape)").matches);
const deviceType = getDeviceType();
switch (deviceType) {
  case "mobile":
    cssFile = "mobile_root.css";
    break;
  case "tablet":
    cssFile = "tablet_root.css";
    break;
  case "desktop":
    cssFile = "desktop_root.css";
    break;
  default:
    break;
}

// Add new CSS file
const link = document.createElement("link");
link.rel = "stylesheet";
link.type = "text/css";
link.href = "../../" + cssFile;
link.setAttribute("data-device", deviceType);
document.head.appendChild(link);

if (
  sessionStorage.getItem("login") == false ||
  sessionStorage.getItem("login") == undefined
) {
  alert();
  if (window.location.href.indexOf("desktop") != -1) {
    window.location.href = "../../login_D.html";
  } else if (window.location.href.indexOf("mobile") != -1) {
    window.location.href = "../../login_M.html";
  }
}
