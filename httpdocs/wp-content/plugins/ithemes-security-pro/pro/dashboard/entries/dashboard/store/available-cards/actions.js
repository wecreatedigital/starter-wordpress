export function receiveAvailableCards( cards ) {
	return {
		type: RECEIVE_AVAILABLE_CARDS,
		cards,
	};
}

export const RECEIVE_AVAILABLE_CARDS = 'RECEIVE_AVAILABLE_CARDS';
