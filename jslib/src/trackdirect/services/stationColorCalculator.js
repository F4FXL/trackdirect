trackdirect.services.stationColorCalculator = {
_colors: [
  "#E53935", // strong red
  "#8E24AA", // purple
  "#3949AB", // vivid blue
  "#00897B", // teal
  "#FB8C00", // orange
  "#6D4C41", // brown
  "#43A047", // medium green (uniquement pour fonds urbains, éviter sur nature)
  "#F4511E", // reddish orange
  "#D81B60", // pink
],
  _stationColorId: {},

  /**
   * Get html (hex) color for specified packet
   * @param {object} packet
   * @return {string}
   */
  getColor: function (packet, trimmed=false) {
    let colorId = this.getColorId(packet);
    if(trimmed === false)
      return this._colors[colorId];
    return this._colors[colorId].replace("#", "");
  },

  /**
   * Get kml color for specified packet
   * @param {object} packet
   * @return {string}
   */
  getKmlColor(packet) {
    let hex = this.getColor(packet);
    let result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    if (result) {
      return "FF" + result[3] + result[2] + result[1];
    }
    return null;
  },

  /**
   * Get color ID for specified packet
   * Color will be selected by station/object name (this make sures that object with different senders still get the same color)
   * @param {object} packet
   * @return {int}
   */
  getColorId: function (packet) {
    let colorId;
    if (packet.station_name in this._stationColorId) {
      // This station has already got a color, use it
      colorId = this._stationColorId[packet.station_name];
      return colorId;
    } else {
      let hash = this._simplehashStr(packet.station_name);
      colorId = Math.abs(hash) % this._colors.length;
      this._stationColorId[packet.station_name] = colorId;
    }

    return colorId;
  },

  /**
   * Get simple hash from string (bad hash, but good for our usage)
   * @param {string} str
   * @return int
   */
  _simplehashStr: function (str) {
    let hash = 0;
    for (const char of str) {
      hash = (hash << 5) - hash + char.charCodeAt(0);
      hash |= 0; // Constrain to 32bit integer
    }
    return hash;
  },
};
