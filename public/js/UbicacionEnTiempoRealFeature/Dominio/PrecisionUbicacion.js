export const PRECISION_MAXIMA_METROS = 39;

export function tienePrecisionSuficiente(precisionMetros) {
    return Number.isFinite(precisionMetros) && precisionMetros <= PRECISION_MAXIMA_METROS;
}
