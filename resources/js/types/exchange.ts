export type ExchangeDetails = {
    id: string;
    name: string;
    budget: string | null;
    exchangeOn: string | null;
    drawn: boolean;
};

export type Participant = {
    id: string;
    name: string;
    wishCount: number;
    revealed: boolean;
    isMine: boolean;
};

export type Exclusion = {
    id: number;
    memberIds: [string, string];
};
